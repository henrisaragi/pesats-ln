<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Enums\ConversationStatus;
use App\Enums\NotificationCategory;
use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Entity types allowed to start a new conversation.
     *
     * @var list<string>
     */
    private const STARTABLE_ENTITY_TYPES = ['perusahaan', 'penangkaran', 'perorangan'];

    public function index(): View
    {
        $user = Auth::user();

        return view('chat.index', [
            'canStart' => $user->can('chat.start') && $this->hasStartableEntity($user),
            'canRespond' => $user->can('chat.respond'),
        ]);
    }

    public function list(): JsonResponse
    {
        $user = Auth::user();

        $queue = $user->can('chat.respond')
            ? Conversation::query()
                ->where('status', ConversationStatus::Waiting)
                ->with('initiator')
                ->latest()
                ->get()
            : collect();

        $participating = fn ($query) => $query->where('initiator_id', $user->id)->orWhere('claimed_by', $user->id);

        $mine = Conversation::query()
            ->where($participating)
            ->whereIn('status', [ConversationStatus::Waiting, ConversationStatus::Active])
            ->with(['initiator', 'claimedBy'])
            ->latest()
            ->get();

        $history = Conversation::query()
            ->where($participating)
            ->where('status', ConversationStatus::Closed)
            ->with(['initiator', 'claimedBy'])
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'queue' => $queue->map(fn (Conversation $c) => $this->formatConversation($c))->values(),
            'mine' => $mine->map(fn (Conversation $c) => $this->formatConversation($c))->values(),
            'history' => $history->map(fn (Conversation $c) => $this->formatConversation($c))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->abortUnlessCanStart();

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $conversation = Conversation::create([
            'initiator_id' => Auth::id(),
            'status' => ConversationStatus::Waiting,
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => Auth::id(),
            'body' => $data['body'],
        ]);

        broadcast(new ChatMessageSent($message));

        $this->notifyQueue($conversation, $message);

        return response()->json([
            'conversation' => $this->formatConversation($conversation->fresh()),
        ], 201);
    }

    public function messages(Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation);

        $conversation->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()->with('sender')->oldest()->get();

        return response()->json([
            'conversation' => $this->formatConversation($conversation),
            'messages' => $messages->map(fn (ChatMessage $message) => $this->formatMessage($message))->values(),
        ]);
    }

    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation);
        abort_if($conversation->status === ConversationStatus::Closed, 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $user = Auth::user();

        if ($conversation->status === ConversationStatus::Waiting && $user->id !== $conversation->initiator_id) {
            $conversation->update([
                'claimed_by' => $user->id,
                'status' => ConversationStatus::Active,
            ]);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $data['body'],
        ]);

        broadcast(new ChatMessageSent($message));

        $this->notifyOtherParticipant($conversation->fresh(), $message);

        return response()->json([
            'message' => $this->formatMessage($message),
            'conversation' => $this->formatConversation($conversation->fresh()),
        ]);
    }

    public function end(Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation);
        abort_if($conversation->status === ConversationStatus::Closed, 403);

        $conversation->update([
            'status' => ConversationStatus::Closed,
            'ended_at' => now(),
            'ended_by' => Auth::id(),
        ]);

        return response()->json(['success' => true]);
    }

    private function authorizeParticipant(Conversation $conversation): void
    {
        $user = Auth::user();

        if ($conversation->status === ConversationStatus::Waiting) {
            abort_unless($user->id === $conversation->initiator_id || $user->can('chat.respond'), 403);

            return;
        }

        abort_unless($user->id === $conversation->initiator_id || $user->id === $conversation->claimed_by, 403);
    }

    private function abortUnlessCanStart(): void
    {
        $user = Auth::user();

        abort_unless($user->can('chat.start') && $this->hasStartableEntity($user), 403);
    }

    private function hasStartableEntity(User $user): bool
    {
        return $user->entity !== null && in_array($user->entity->entity_type, self::STARTABLE_ENTITY_TYPES, true);
    }

    private function notifyQueue(Conversation $conversation, ChatMessage $message): void
    {
        $recipients = User::permission('chat.respond')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AppNotification(
            NotificationCategory::InformasiLain,
            __('Pesan Chat Baru'),
            Str::limit($message->body, 80),
            route('chat.index', ['conversation' => $conversation->id]),
        ));
    }

    private function notifyOtherParticipant(Conversation $conversation, ChatMessage $message): void
    {
        $recipientId = $message->sender_id === $conversation->initiator_id
            ? $conversation->claimed_by
            : $conversation->initiator_id;

        if ($recipientId === null) {
            $this->notifyQueue($conversation, $message);

            return;
        }

        $recipient = User::find($recipientId);

        if ($recipient === null) {
            return;
        }

        Notification::send([$recipient], new AppNotification(
            NotificationCategory::InformasiLain,
            __('Pesan Chat Baru'),
            Str::limit($message->body, 80),
            route('chat.index', ['conversation' => $conversation->id]),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatConversation(Conversation $conversation): array
    {
        $lastMessage = $conversation->messages()->latest()->first();

        $unreadCount = $conversation->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->count();

        return [
            'id' => $conversation->id,
            'status' => $conversation->status->value,
            'status_label' => $conversation->status->label(),
            'initiator_id' => $conversation->initiator_id,
            'initiator_name' => $conversation->initiator?->name,
            'claimed_by' => $conversation->claimed_by,
            'claimed_by_name' => $conversation->claimedBy?->name,
            'last_message' => $lastMessage?->body,
            'last_message_at' => $lastMessage?->created_at?->diffForHumans(),
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
            'mine' => $message->sender_id === Auth::id(),
            'read' => $message->read_at !== null,
        ];
    }
}
