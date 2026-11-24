<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lewat actingAs()/withSession() (bukan mengalirkan cookie sesi antar beberapa panggilan
 * $this->get()/post() terpisah) - Laravel test client tidak otomatis meneruskan cookie sesi
 * seperti browser sungguhan, jadi setiap skenario "sudah login lalu request lagi setelah waktu
 * tertentu" di sini disimulasikan lewat SATU request dengan state (last_activity_at,
 * current_session_id null supaya cabang pencocokan session id di EnsureAuthSessionIsFresh tidak
 * ikut campur) yang sudah disiapkan langsung di database - lihat catatan yang sama di
 * ConcurrentSessionTest.
 */
class IdleSessionReauthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Idle User',
            'email' => uniqid('idle', true).'@example.test',
            'username' => uniqid('idle', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);
    }

    public function test_a_stale_session_redirects_to_reauthenticate_without_otp(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['last_activity_at' => now()->subMinutes(61)])->save();

        $response = $this->actingAs($user)->get(route('dashboards.index'));

        $response->assertRedirect(route('reauthenticate.show'));
        $this->assertGuest();
    }

    public function test_a_fresh_session_stays_logged_in(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['last_activity_at' => now()->subMinutes(30)])->save();

        $this->actingAs($user)->get(route('dashboards.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_correct_password_on_reauthenticate_restores_the_session_without_otp(): void
    {
        $user = $this->makeUser();
        Mail::fake();

        $response = $this->withSession(['reauth_user_id' => $user->id])
            ->post(route('reauthenticate.store'), ['password' => 'password']);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->current_session_id);
        $response->assertCookie('reauth_user', (string) $user->id);
        Mail::assertNothingSent();
    }

    public function test_wrong_password_on_reauthenticate_fails(): void
    {
        $user = $this->makeUser();

        $response = $this->withSession(['reauth_user_id' => $user->id])
            ->post(route('reauthenticate.store'), ['password' => 'wrong-password']);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_reauthenticate_store_redirects_to_login_without_a_pending_timeout(): void
    {
        $response = $this->post(route('reauthenticate.store'), ['password' => 'anything']);

        $response->assertRedirect(route('login'));
    }

    public function test_reauthenticate_page_redirects_to_login_without_a_pending_timeout(): void
    {
        $this->get(route('reauthenticate.show'))->assertRedirect(route('login'));
    }

    public function test_stale_session_on_ajax_poll_returns_401_instead_of_redirect(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['last_activity_at' => now()->subMinutes(61)])->save();

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertUnauthorized();
        $response->assertSessionMissing('reauth_intended_url');
        $this->assertGuest();
    }

    public function test_expired_session_with_reauth_cookie_redirects_to_password_only_page(): void
    {
        $user = $this->makeUser();

        $this->withCookie('reauth_user', (string) $user->id)
            ->get(route('dashboards.index'))
            ->assertRedirect(route('reauthenticate.show'));

        $this->withCookie('reauth_user', (string) $user->id)
            ->get(route('reauthenticate.show'))
            ->assertOk()
            ->assertSee($user->name);
    }

    public function test_reauth_cookie_allows_password_only_login_after_session_is_gone(): void
    {
        $user = $this->makeUser();

        $response = $this->withCookie('reauth_user', (string) $user->id)
            ->post(route('reauthenticate.store'), ['password' => 'password']);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_without_reauth_cookie_still_goes_to_login(): void
    {
        $this->get(route('root'))->assertRedirect(route('login'));
    }

    public function test_logout_clears_the_reauth_cookie(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('logout'))
            ->assertCookieExpired('reauth_user');
    }

    public function test_background_poll_does_not_count_as_activity(): void
    {
        $user = $this->makeUser();
        $lastActivity = now()->subMinutes(30)->startOfSecond();
        $user->forceFill(['last_activity_at' => $lastActivity])->save();

        $this->actingAs($user)
            ->getJson(route('notifications.index'), ['X-Background-Poll' => '1'])
            ->assertOk();

        $this->assertTrue($user->fresh()->last_activity_at->equalTo($lastActivity));
    }

    public function test_background_poll_on_stale_session_still_returns_401(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['last_activity_at' => now()->subMinutes(61)])->save();

        $this->actingAs($user)
            ->getJson(route('notifications.index'), ['X-Background-Poll' => '1'])
            ->assertUnauthorized();

        $this->assertGuest();
    }

    public function test_user_initiated_request_refreshes_activity(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['last_activity_at' => now()->subMinutes(30)])->save();

        $this->actingAs($user)->getJson(route('notifications.index'))->assertOk();

        $this->assertTrue($user->fresh()->last_activity_at->gt(now()->subMinute()));
    }

    public function test_reauthenticate_after_idle_returns_to_the_page_that_timed_out(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['last_activity_at' => now()->subMinutes(61)])->save();

        $this->actingAs($user)->get(route('dashboards.index').'?year=2026')
            ->assertRedirect(route('reauthenticate.show'));

        $this->post(route('reauthenticate.store'), ['password' => 'password'])
            ->assertRedirect(route('dashboards.index').'?year=2026');
    }

    public function test_reauthenticate_after_expired_session_returns_to_the_requested_page(): void
    {
        $user = $this->makeUser();

        $this->withCookie('reauth_user', (string) $user->id)
            ->get(route('dashboards.index').'?year=2025')
            ->assertRedirect(route('reauthenticate.show'));

        $this->withCookie('reauth_user', (string) $user->id)
            ->post(route('reauthenticate.store'), ['password' => 'password'])
            ->assertRedirect(route('dashboards.index').'?year=2025');
    }
}
