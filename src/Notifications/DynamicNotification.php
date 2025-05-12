<?php

namespace DynamicNotifier\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DynamicNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $channel;
    public string $body;
    public ?string $header;

    public function __construct(string $channel, string $body, ?string $header = null)
    {
        $this->channel = $channel;
        $this->body = $body;
        $this->header = $header;
    }

    public function via($notifiable): array
    {
        return [$this->channel, 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->header ?? 'Notification')
            ->line($this->body);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->header ?? 'Notification',
            'message' => $this->body,
            'notifiable_id' => $notifiable->id,
            'channel' => $this->channel,
            'delivered_at' => now(),
        ];
    }
}
