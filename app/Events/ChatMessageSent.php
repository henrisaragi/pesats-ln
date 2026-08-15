<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ConversationStatus;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatMessage $message,
    ) {}

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;

        $channels = [new PrivateChannel('conversation.'.$conversation->id)];

        if ($conversation->status === ConversationStatus::Waiting) {
            $channels[] = new PrivateChannel('chat.queue');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender?->name,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
