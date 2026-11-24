<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventiNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $message,
        public readonly string $url,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array{key: string, title: string, message: string, url: string}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)
            ->action('Consulter Eventi', $this->url);
    }
}
