<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class StudentNotificationAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info',
        public ?array $data = null,
        public ?string $actionUrl = null,
        public ?string $actionText = null,
        public string $priority = 'normal'
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', WebPushChannel::class];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'data' => $this->data,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'priority' => $this->priority,
            'created_at' => now()->toISOString(),
            'is_read' => false,
        ]);
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->message)
            ->icon('/favicon.ico')
            ->badge('/favicon.ico')
            ->data([
                'id' => $this->id,
                'type' => $this->type,
                'action_url' => $this->actionUrl,
                'priority' => $this->priority,
            ])
            ->options(['TTL' => 1000]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'data' => $this->data,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'priority' => $this->priority,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('notifications.' . $this->notifiable->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'student.notification';
    }
}
