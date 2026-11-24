<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Reservation;
use App\Notifications\EventiNotification;
use App\Services\NotificationDeliveryService;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StripePaymentService $stripe,
        NotificationDeliveryService $delivery,
    )
    {
        $payload = $request->getContent();

        if (! $stripe->hasValidWebhookSignature($payload, $request->header('Stripe-Signature'))) {
            return response('Invalid Stripe signature.', 400);
        }

        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response('Invalid Stripe payload.', 400);
        }

        $type = $event['type'] ?? null;
        $session = $event['data']['object'] ?? null;

        if (! is_string($type) || ! is_array($session) || ! is_string($session['id'] ?? null)) {
            return response('Invalid Stripe event.', 400);
        }

        if ($type === 'checkout.session.expired' || $type === 'checkout.session.async_payment_failed') {
            $reservation = Reservation::where('stripe_checkout_session_id', $session['id'])->first();

            if ($reservation && $reservation->statut === 'en_attente') {
                $reservation->update([
                    'statut' => 'annulee',
                    'payment_status' => $type === 'checkout.session.expired' ? 'expired' : 'failed',
                    'stripe_checkout_url' => null,
                    'expires_at' => null,
                ]);

                $reservation->load(['user', 'event']);
                $delivery->send($reservation->user, new EventiNotification(
                    "reservation-payment:{$reservation->id}:{$type}",
                    'Réservation annulée',
                    $type === 'checkout.session.expired'
                        ? 'La session de paiement a expiré ; votre réservation a été annulée.'
                        : 'Le paiement Stripe a échoué ; votre réservation a été annulée.',
                    route('events.reserve', $reservation->event),
                ));
            }

            return response()->noContent();
        }

        if (! in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return response()->noContent();
        }

        $reservation = Reservation::where('stripe_checkout_session_id', $session['id'])->first();

        if (! $reservation) {
            Log::warning('Stripe webhook references an unknown Checkout session.', [
                'session_id' => $session['id'],
            ]);

            return response()->noContent();
        }

        $metadata = $session['metadata'] ?? [];
        $paymentIntent = $session['payment_intent'] ?? null;
        $metadataMatches = is_array($metadata)
            && ($metadata['reservation_id'] ?? null) === (string) $reservation->id
            && ($metadata['user_id'] ?? null) === (string) $reservation->user_id;

        if (
            ! $metadataMatches
            || strtolower((string) ($session['currency'] ?? '')) !== 'usd'
            || (int) ($session['amount_total'] ?? -1) !== (int) $reservation->stripe_amount_cents
        ) {
            Log::error('Stripe payment details did not match the reservation.', [
                'reservation_id' => $reservation->id,
                'session_id' => $session['id'],
            ]);

            return response('Stripe payment details do not match.', 400);
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            return response()->noContent();
        }

        $outcome = DB::transaction(function () use ($reservation, $session, $paymentIntent): string {
            $lockedReservation = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (
                $lockedReservation->statut === 'confirmee'
                && $lockedReservation->payment_status === 'paid'
            ) {
                return 'already_confirmed';
            }

            if ($lockedReservation->stripe_checkout_session_id !== $session['id']) {
                return 'stale';
            }

            $event = Event::whereKey($lockedReservation->event_id)->lockForUpdate()->firstOrFail();
            $occupied = Reservation::where('event_id', $event->id)
                ->where('id', '!=', $lockedReservation->id)
                ->where(function ($query): void {
                    $query->where('statut', 'confirmee')
                        ->orWhere(function ($query): void {
                            $query->where('statut', 'en_attente')
                                ->where('expires_at', '>', now());
                        });
                })
                ->sum('nb_places');

            $paymentIntentId = is_string($paymentIntent) ? $paymentIntent : null;

            if (
                $event->statut !== 'actif'
                || (int) $occupied + $lockedReservation->nb_places > $event->capacite
            ) {
                $lockedReservation->update([
                    'payment_status' => 'review',
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'paid_at' => now(),
                ]);

                return 'review';
            }

            $lockedReservation->update([
                'statut' => 'confirmee',
                'payment_status' => 'paid',
                'stripe_payment_intent_id' => $paymentIntentId,
                'expires_at' => null,
                'paid_at' => now(),
            ]);

            return 'confirmed';
        });

        if ($outcome === 'review') {
            Log::critical('A paid Stripe reservation requires capacity review and may need a refund.', [
                'reservation_id' => $reservation->id,
                'session_id' => $session['id'],
            ]);
        } elseif ($outcome === 'stale') {
            Log::warning('A stale Stripe Checkout session completed after a retry.', [
                'reservation_id' => $reservation->id,
                'session_id' => $session['id'],
            ]);
        }

        if (in_array($outcome, ['confirmed', 'review'], true)) {
            $reservation->load(['user', 'event']);
            $delivery->send($reservation->user, new EventiNotification(
                "reservation-payment:{$reservation->id}:{$outcome}",
                $outcome === 'confirmed' ? 'Paiement confirmé' : 'Paiement reçu, vérification en cours',
                $outcome === 'confirmed'
                    ? 'Votre paiement est confirmé et votre réservation pour « '.$reservation->event->titre.' » est validée.'
                    : 'Votre paiement a été reçu, mais la disponibilité des places doit être vérifiée.',
                route('events.reserve', $reservation->event),
            ));
        }

        return response()->noContent();
    }
}
