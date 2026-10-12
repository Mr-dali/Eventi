<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::withPlaces()
            ->where('statut', 'actif')
            ->orderBy('date_evenement')
            ->get();

        $reserved    = auth()->user()->reservations()->pluck('event_id')->all();
        $totalEvents = $events->count();

        return view('events.index', compact('events', 'reserved', 'totalEvents'));
    }

    public function reserve(Event $event)
    {
        $event = Event::withPlaces()->findOrFail($event->id);

        if ($event->statut !== 'actif') {
            return redirect()->route('events.index');
        }

        $dejaRese         = Reservation::where('user_id', auth()->id())->where('event_id', $event->id)->exists();
        $places_restantes = $event->places_restantes;

        return view('events.reserve', compact('event', 'dejaRese', 'places_restantes'));
    }

    public function storeReservation(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate(
            ['nb_places' => ['required', 'integer', 'min:1']],
            ['nb_places.*' => 'Le nombre de places doit être au moins 1.']
        );
        $nb = (int) $data['nb_places'];
        $userId = auth()->id();

        // Verrou sur l'événement : évite le surbooking si deux personnes réservent en même temps.
        DB::transaction(function () use ($event, $nb, $userId) {
            $locked = Event::whereKey($event->id)->where('statut', 'actif')->lockForUpdate()->firstOrFail();

            if (Reservation::where('user_id', $userId)->where('event_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['nb_places' => 'Cette réservation existe déjà.']);
            }

            $restantes = $locked->capacite
                - (int) Reservation::where('event_id', $locked->id)->where('statut', 'confirmee')->sum('nb_places');

            if ($nb > $restantes) {
                throw ValidationException::withMessages(['nb_places' => "Seulement {$restantes} place(s) disponible(s)."]);
            }

            Reservation::create([
                'user_id'   => $userId,
                'event_id'  => $locked->id,
                'nb_places' => $nb,
                'statut'    => 'confirmee',
            ]);
        });

        return redirect()->route('events.reserve', $event)->with('reserved', $nb);
    }
}
