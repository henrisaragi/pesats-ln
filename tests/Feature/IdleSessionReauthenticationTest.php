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

        $response = $this->actingAs($user)->get(route('second', ['dashboards', 'index']));

        $response->assertRedirect(route('reauthenticate.show'));
        $this->assertGuest();
    }

    public function test_a_fresh_session_stays_logged_in(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['last_activity_at' => now()->subMinutes(30)])->save();

        $this->actingAs($user)->get(route('second', ['dashboards', 'index']));

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
}
