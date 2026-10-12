<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $confirmed = Reservation::where('reservations.statut', 'confirmee');

        $stats = [
            'total_events' => Event::count(),
            'total_users'  => User::where('role', 'user')->count(),
            'total_resa'   => (clone $confirmed)->count(),
            'revenus'      => (clone $confirmed)
                ->join('events', 'events.id', '=', 'reservations.event_id')
                ->selectRaw('COALESCE(SUM(events.prix * reservations.nb_places), 0) AS total')
                ->value('total'),
        ];

        $derniers_events = Event::withPlaces()->latest()->take(5)->get();
        $dernieres_resa  = Reservation::with(['user', 'event'])->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'derniers_events', 'dernieres_resa'));
    }
}
