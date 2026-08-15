<?php

declare(strict_types=1);

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Real-time push is scoped to chat only (see App\Notifications\AppNotification::via()) - no
// general "App.Models.User.{id}" channel is registered since nothing broadcasts on it.

Broadcast::channel('conversation.{conversation}', function ($user, Conversation $conversation) {
    if ($conversation->status === ConversationStatus::Waiting) {
        return $user->can('chat.respond') || $user->id === $conversation->initiator_id;
    }

    return $user->id === $conversation->initiator_id || $user->id === $conversation->claimed_by;
});

Broadcast::channel('chat.queue', fn ($user) => $user->can('chat.respond'));
