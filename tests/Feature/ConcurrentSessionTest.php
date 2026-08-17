<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ConcurrentSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Concurrent User',
            'email' => uniqid('concurrent', true).'@example.test',
            'username' => uniqid('concurrent', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);
    }

    public function test_second_login_attempt_is_blocked_while_a_session_is_active_elsewhere(): void
    {
        $user = $this->makeUser();

        $user->forceFill([
            'current_session_id' => 'some-other-device-session-id',
            'last_activity_at' => now(),
        ])->save();

        Mail::fake();

        $response = $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
        Mail::assertNothingSent();
    }

    public function test_login_is_allowed_again_once_the_active_session_has_gone_idle(): void
    {
        $user = $this->makeUser();

        $user->forceFill([
            'current_session_id' => 'stale-device-session-id',
            'last_activity_at' => now()->subHours(2),
        ])->save();

        Mail::fake();

        $response = $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('login.otp.show'));
        Mail::assertSent(OtpMail::class);
    }

    /**
     * Lewat actingAs() + satu request (bukan mensimulasikan 2 request terpisah lewat cookie
     * sungguhan - Laravel test client tidak otomatis meneruskan cookie sesi antar panggilan
     * $this->get()/post(), beda dari browser sungguhan) supaya tidak bergantung pada
     * EnsureAuthSessionIsFresh mencocokkan session id request dengan current_session_id yang
     * tersimpan (current_session_id sengaja dibiarkan null di sini - lihat cabang "self-heal" di
     * middleware itu - supaya fokus test murni pada AuthenticationController::logout() memanggil
     * releaseSessionLock(), bukan pada mekanisme pencocokan sesi).
     */
    public function test_logout_releases_the_session_lock_allowing_immediate_relogin(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/logout');

        $this->assertNull($user->fresh()->current_session_id);
        $this->assertNull($user->fresh()->last_activity_at);

        Mail::fake();
        $response = $this->post('/login', ['login' => $user->email, 'password' => 'password']);
        $response->assertRedirect(route('login.otp.show'));
    }
}
