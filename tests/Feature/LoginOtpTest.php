<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'OTP User',
            'email' => uniqid('otp', true).'@example.test',
            'username' => uniqid('otp', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);
    }

    public function test_correct_credentials_redirect_to_the_otp_page_instead_of_logging_in(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $response = $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('login.otp.show'));
        $this->assertGuest();
        Mail::assertSent(OtpMail::class);
    }

    public function test_otp_page_renders_with_a_pending_login(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $response = $this->get(route('login.otp.show'));

        $response->assertOk();
        $response->assertSee(__('Verifikasi Kode OTP'));
    }

    public function test_wrong_credentials_fail_without_sending_an_otp(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $response = $this->post('/login', ['login' => $user->email, 'password' => 'wrong-password']);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
        Mail::assertNothingSent();
    }

    public function test_correct_otp_completes_the_login(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $otpCode = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$otpCode) {
            $otpCode = $mail->otpCode;

            return true;
        });

        $response = $this->post('/login/otp', ['otp' => $otpCode]);

        $response->assertRedirect(route('dashboards.index'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_wrong_otp_fails_and_does_not_log_in(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $response = $this->post('/login/otp', ['otp' => '000000']);

        $response->assertSessionHasErrors('otp');
        $this->assertGuest();
    }

    public function test_expired_otp_fails(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $otpCode = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$otpCode) {
            $otpCode = $mail->otpCode;

            return true;
        });

        $this->travel(11)->minutes();

        $response = $this->post('/login/otp', ['otp' => $otpCode]);

        $response->assertSessionHasErrors('otp');
        $this->assertGuest();
    }

    public function test_resend_otp_issues_a_new_code(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $firstCode = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$firstCode) {
            $firstCode = $mail->otpCode;

            return true;
        });

        $this->post('/login/otp/resend');

        Mail::assertSent(OtpMail::class, 2);

        // Kode lama tidak lagi valid setelah resend (di-replace, bukan ditambah).
        $response = $this->post('/login/otp', ['otp' => $firstCode]);
        $response->assertSessionHasErrors('otp');
    }

    public function test_otp_page_redirects_to_login_without_a_pending_login(): void
    {
        $this->get('/login/otp')->assertRedirect(route('login'));
    }

    public function test_fresh_login_always_lands_on_the_dashboard_even_with_an_intended_url(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->get(route('kuota-saya.index'))->assertRedirect(route('login'));
        $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $otpCode = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$otpCode) {
            $otpCode = $mail->otpCode;

            return true;
        });

        $this->post('/login/otp', ['otp' => $otpCode])->assertRedirect(route('dashboards.index'));
    }
}
