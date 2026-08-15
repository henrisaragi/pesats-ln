<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly NotificationCategory $category,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $url = null,
    ) {
        // Dedicated queue so `queue:work --queue=notifications` never touches unrelated jobs
        // already sitting on the "default" queue (e.g. region-sync jobs).
        $this->onQueue('notifications');
    }

    /**
     * Database only - real-time (broadcast) push is reserved for the chat feature
     * (App\Events\ChatMessageSent). General notifications are picked up by the bell
     * dropdown's polling instead (resources/js/components/notification-dropdown.js).
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category->value,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
