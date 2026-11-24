<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\EventiNotification;
use App\Services\NotificationDeliveryService;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send event reminders three, two, and one hour before an event';

    public function handle(NotificationDeliveryService $delivery): int
    {
        foreach ([3, 2, 1] as $hours) {
            $from = now()->addHours($hours)->startOfMinute();
            $until = $from->copy()->addMinute();

            $reservations = Reservation::with(['user', 'event'])
                ->where('statut', 'confirmee')
                ->whereHas('event', fn ($query) => $query
                    ->where('statut', 'actif')
                    ->whereBetween('date_evenement', [$from, $until]))
                ->get();

            foreach ($reservations as $reservation) {
                $key = "event-reminder:{$reservation->id}:{$hours}";
                $alreadySent = $reservation->user->notifications()
                    ->where('type', EventiNotification::class)
                    ->where('data->key', $key)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $delivery->send($reservation->user, new EventiNotification(
                    $key,
                    'Rappel : événement dans '.$hours.' heure(s)',
                    'Votre événement « '.$reservation->event->titre.' » commence dans '.$hours.' heure(s).',
                    route('events.reserve', $reservation->event),
                ));
            }
        }

        return self::SUCCESS;
    }
}
