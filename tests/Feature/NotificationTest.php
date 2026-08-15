<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationCategory;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'User Test',
            'email' => uniqid('user', true).'@example.test',
            'username' => uniqid('user', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);
    }

    public function test_user_sees_only_their_own_notifications(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $user->notify(new AppNotification(NotificationCategory::Pengumuman, 'Judul', 'Pesan'));
        $other->notify(new AppNotification(NotificationCategory::Pengumuman, 'Punya Orang Lain', 'Pesan'));

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk();
        $response->assertJsonCount(1, 'notifications');
        $response->assertJsonPath('notifications.0.title', 'Judul');
        $response->assertJsonPath('unread_count', 1);
    }

    public function test_notification_payload_includes_category_and_url(): void
    {
        $user = $this->makeUser();
        $user->notify(new AppNotification(NotificationCategory::InformasiLain, 'Judul', 'Pesan', '/some/url'));

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertJsonPath('notifications.0.category', NotificationCategory::InformasiLain->value);
        $response->assertJsonPath('notifications.0.url', '/some/url');
        $response->assertJsonPath('notifications.0.read', false);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = $this->makeUser();
        $user->notify(new AppNotification(NotificationCategory::Pengumuman, 'Judul', 'Pesan'));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('notifications.read', $notification))
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $other->notify(new AppNotification(NotificationCategory::Pengumuman, 'Judul', 'Pesan'));
        $notification = $other->notifications()->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read_only_affects_the_authenticated_user(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $user->notify(new AppNotification(NotificationCategory::Pengumuman, 'A', 'A'));
        $user->notify(new AppNotification(NotificationCategory::InformasiLain, 'B', 'B'));
        $other->notify(new AppNotification(NotificationCategory::Pengumuman, 'C', 'C'));

        $this->actingAs($user)->postJson(route('notifications.read-all'))->assertOk();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $other->fresh()->unreadNotifications()->count());
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $this->getJson(route('notifications.index'))->assertUnauthorized();
    }
}
