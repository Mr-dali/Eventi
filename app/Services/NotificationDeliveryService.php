<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EventiNotification;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class NotificationDeliveryService
{
    public function send(User $user, EventiNotification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (TransportExceptionInterface $exception) {
            Log::error('Eventi email notification could not be delivered.', [
                'user_id' => $user->id,
                'notification_key' => $notification->key,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param iterable<User> $users
     */
    public function sendToUsers(iterable $users, EventiNotification $notification): void
    {
        foreach ($users as $user) {
            $this->send($user, $notification);
        }
    }
}
