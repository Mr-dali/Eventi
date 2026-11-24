<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Reservation;
use App\Notifications\EventiNotification;
use App\Services\StripeCheckoutException;
use App\Services\NotificationDeliveryService;
use App\Services\StripePaymentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::withPlaces()
            ->where('statut', 'actif')
            ->orderBy('date_evenement')
            ->get();

        $reserved = auth()->user()->reservations()
            ->where(function ($query): void {
                $query->where('statut', 'confirmee')
                    ->orWhere(function ($query): void {
                        $query->where('statut', 'en_attente')
                            ->where('expires_at', '>', now());
                    });
            })
            ->pluck('event_id')
            ->all();
        $totalEvents = $events->count();

        return view('events.index', compact('events', 'reserved', 'totalEvents'));
    }

    public function reserve(Event $event, StripePaymentService $stripe)
    {
        $event = Event::withPlaces()->findOrFail($event->id);

        if ($event->statut !== 'actif') {
            return redirect()->route('events.index');
        }

        $reservation = Reservation::where('user_id', auth()->id())
            ->where('event_id', $event->id)
            ->first();
        $dejaRese = $reservation && (
            $reservation->statut === 'confirmee'
            || ($reservation->statut === 'en_attente' && $reservation->expires_at?->isFuture())
        );
        $checkoutUrl = $dejaRese && $reservation->statut === 'en_attente'
            ? $reservation->stripe_checkout_url
            : null;
        $places_restantes = $event->places_restantes;
        $usdUnitCents = $stripe->unitAmountInUsdCents($event->prix);
        $canPay = (float) $event->prix <= 0 || (
            $stripe->isConfigured() && $usdUnitCents !== null && $usdUnitCents > 0
        );
        $tndPerUsd = $stripe->tndPerUsd();

        return view('events.reserve', compact(
            'event',
            'dejaRese',
            'places_restantes',
            'reservation',
            'checkoutUrl',
            'usdUnitCents',
            'canPay',
            'tndPerUsd',
        ));
    }

    public function storeReservation(
        Request $request,
        Event $event,
        StripePaymentService $stripe,
        NotificationDeliveryService $delivery,
    ): RedirectResponse
    {
        $data = $request->validate(
            ['nb_places' => ['required', 'integer', 'min:1']],
            ['nb_places.*' => 'Le nombre de places doit être au moins 1.']
        );
        $nb = (int) $data['nb_places'];
        $userId = auth()->id();
        $existing = Reservation::where('user_id', $userId)
            ->where('event_id', $event->id)
            ->first();

        if ($existing?->statut === 'confirmee') {
            throw ValidationException::withMessages(['nb_places' => 'Cette réservation est déjà confirmée.']);
        }

        if (in_array($existing?->payment_status, ['paid', 'review'], true)) {
            throw ValidationException::withMessages(['payment' => 'Ce paiement est en cours de vérification.']);
        }

        if ($existing?->statut === 'en_attente' && $existing->expires_at?->isFuture()) {
            if ($existing->stripe_checkout_url) {
                return redirect()->away($existing->stripe_checkout_url);
            }

            throw ValidationException::withMessages(['payment' => 'La session de paiement est en cours de création. Réessayez dans quelques instants.']);
        }

        $reservation = DB::transaction(function () use ($event, $nb, $userId, $stripe) {
            $locked = Event::whereKey($event->id)->where('statut', 'actif')->lockForUpdate()->firstOrFail();
            $paid = (float) $locked->prix > 0;
            $rate = $paid ? $stripe->tndPerUsd() : null;
            $unitAmount = $paid ? $stripe->unitAmountInUsdCents($locked->prix) : null;

            if ($paid && (! $stripe->isConfigured() || $unitAmount === null || $unitAmount < 1)) {
                throw ValidationException::withMessages([
                    'payment' => 'Le paiement Stripe est indisponible : vérifiez la clé API, le secret webhook et le taux TND/USD.',
                ]);
            }

            $existing = Reservation::where('user_id', $userId)
                ->where('event_id', $locked->id)
                ->lockForUpdate()
                ->first();

            if ($existing?->statut === 'confirmee') {
                throw ValidationException::withMessages(['nb_places' => 'Cette réservation est déjà confirmée.']);
            }

            $occupied = Reservation::where('event_id', $locked->id)
                ->where(function ($query): void {
                    $query->where('statut', 'confirmee')
                        ->orWhere(function ($query): void {
                            $query->where('statut', 'en_attente')
                                ->where('expires_at', '>', now());
                        });
                })
                ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                ->sum('nb_places');
            $restantes = $locked->capacite - (int) $occupied;

            if ($nb > $restantes) {
                throw ValidationException::withMessages(['nb_places' => "Seulement {$restantes} place(s) disponible(s)."]);
            }

            $attributes = [
                'user_id'   => $userId,
                'event_id'  => $locked->id,
                'nb_places' => $nb,
                'statut' => $paid ? 'en_attente' : 'confirmee',
                'payment_status' => $paid ? 'pending' : 'not_required',
                'stripe_checkout_session_id' => null,
                'stripe_checkout_url' => null,
                'stripe_payment_intent_id' => null,
                'stripe_amount_cents' => $paid ? $unitAmount * $nb : null,
                'stripe_tnd_per_usd' => $rate,
                'expires_at' => $paid ? now()->addMinutes(45) : null,
                'paid_at' => null,
            ];

            if ($existing) {
                $existing->update($attributes);

                return $existing->fresh();
            }

            return Reservation::create($attributes);
        });

        if ($reservation->statut === 'confirmee') {
            $notificationKey = 'reservation-created:'.$reservation->id.':'.(
                $reservation->created_at?->timestamp ?? now()->timestamp
            );

            $delivery->send($request->user(), new EventiNotification(
                $notificationKey,
                'Réservation confirmée',
                'Votre réservation pour « '.$event->titre.' » est confirmée.',
                route('events.reserve', $event),
            ));

            return redirect()->route('events.reserve', $event)->with('reserved', $nb);
        }

        try {
            $session = $stripe->createCheckoutSession($reservation, $event);
        } catch (StripeCheckoutException|ConnectionException $exception) {
            if ($exception instanceof ConnectionException) {
                Log::error('Could not connect to Stripe Checkout.', [
                    'reservation_id' => $reservation->id,
                ]);
            }

            $reservation->update([
                'statut' => 'annulee',
                'payment_status' => 'failed',
                'stripe_checkout_url' => null,
                'expires_at' => null,
            ]);

            $message = $exception instanceof StripeCheckoutException
                ? $exception->getMessage()
                : 'Le service Stripe est inaccessible. Réessayez plus tard.';

            return redirect()->route('events.reserve', $event)->withErrors(['payment' => $message]);
        }

        $reservation->update([
            'stripe_checkout_session_id' => $session['id'],
            'stripe_checkout_url' => $session['url'],
        ]);
        $notificationKey = 'reservation-created:'.$reservation->id.':'.(
            $reservation->expires_at?->timestamp ?? now()->timestamp
        );

        $delivery->send($request->user(), new EventiNotification(
            $notificationKey,
            'Réservation en attente de paiement',
            'Votre réservation pour « '.$event->titre.' » est gardée pendant 45 minutes. Finalisez le paiement Stripe pour la confirmer.',
            route('events.reserve', $event),
        ));

        return redirect()->away($session['url']);
    }
}
