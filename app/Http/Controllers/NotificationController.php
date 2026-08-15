<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $notifications = $user->notifications()->latest()->limit(30)->get();

        return response()->json([
            'notifications' => $notifications->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'category' => $notification->data['category'] ?? null,
                'title' => $notification->data['title'] ?? null,
                'message' => $notification->data['message'] ?? null,
                'url' => $notification->data['url'] ?? null,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->diffForHumans(),
            ]),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): JsonResponse
    {
        $this->authorizeOwnership($notification);

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function authorizeOwnership(DatabaseNotification $notification): void
    {
        abort_unless(
            $notification->notifiable_type === User::class && $notification->notifiable_id === Auth::id(),
            403
        );
    }
}
