<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Mail\OtpMail;
use App\Models\User;
use App\Services\Mail\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthenticationService
{
    /**
     * "maksimal 10 Menit untuk expired otp" - lihat claude/auth-task.md.
     */
    public const OTP_TTL_MINUTES = 10;

    /**
     * "session dibuat maksimal 1 jam kalau tidak digunakan" - lihat claude/auth-task.md.
     * Dipakai App\Http\Middleware\EnsureAuthSessionIsFresh, bukan hanya di sini, supaya kedua
     * tempat tidak punya angka ajaib yang bisa saling tidak sinkron.
     */
    public const IDLE_TIMEOUT_MINUTES = 60;

    public function __construct(
        private readonly EmailService $emailService,
    ) {}

    /**
     * Cek kredensial TANPA login (Auth::login belum dipanggil) - login baru benar-benar
     * ditetapkan setelah OTP diverifikasi lewat completeLogin(), lihat claude/auth-task.md.
     */
    public function validateCredentials(string $login, string $password): ?User
    {
        $user = User::query()->where($this->loginField($login), $login)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    /**
     * "jika user yang sama login pada device lain, maka gagal masuk" - dicek SEBELUM OTP
     * dikirim (percuma kirim OTP kalau nanti tetap ditolak). Sesi dianggap masih aktif kalau
     * penanda sesi ada DAN belum idle lebih dari IDLE_TIMEOUT_MINUTES - begitu sesi lama idle
     * (atau logout eksplisit, lihat releaseSessionLock()), login baru otomatis diperbolehkan
     * lagi tanpa perlu campur tangan manual.
     */
    public function hasActiveSessionElsewhere(User $user): bool
    {
        return $user->current_session_id !== null
            && $user->last_activity_at !== null
            && $user->last_activity_at->gt(now()->subMinutes(self::IDLE_TIMEOUT_MINUTES));
    }

    public function sendOtp(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'otp_code_hash' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ])->save();

        $this->emailService->send($user->email, new OtpMail($code, self::OTP_TTL_MINUTES));

        // Sementara, selama email service OTP belum terintegrasi (lihat config/logging.php
        // channel 'otp') - hapus baris ini begitu pengiriman email OTP sungguhan sudah aktif.
        Log::channel('otp')->info("OTP untuk {$user->email} ({$user->username}): {$code}", [
            'expires_at' => $user->otp_expires_at?->toDateTimeString(),
        ]);
    }

    public function verifyOtp(User $user, string $code): bool
    {
        if ($user->otp_code_hash === null || $user->otp_expires_at === null) {
            return false;
        }

        if ($user->otp_expires_at->isPast()) {
            return false;
        }

        return Hash::check($code, $user->otp_code_hash);
    }

    /**
     * Login sungguhan ditetapkan di sini (dipanggil setelah OTP terverifikasi, ATAU dari alur
     * input ulang password saat sesi idle - lihat App\Http\Controllers\Auth\ReauthenticateController,
     * yang memang sengaja tidak lewat OTP lagi sesuai permintaan). session()->regenerate() DULU baru
     * current_session_id diisi, supaya id yang disimpan adalah id sesi yang baru, bukan yang lama.
     */
    public function completeLogin(User $user, Request $request, bool $remember = false): void
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill([
            'otp_code_hash' => null,
            'otp_expires_at' => null,
            'current_session_id' => $request->session()->getId(),
            'last_activity_at' => now(),
        ])->save();
    }

    /**
     * Dipanggil saat logout eksplisit supaya login berikutnya (device manapun) tidak ketahan
     * oleh penanda "sesi masih aktif" milik sesi yang barusan sengaja diakhiri sendiri.
     */
    public function releaseSessionLock(User $user): void
    {
        $user->forceFill(['current_session_id' => null, 'last_activity_at' => null])->save();
    }

    /**
     * @param  array{name: string, username: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'onboarding_completed' => false,
        ]);
    }

    /**
     * @return array{user: User, token: string}|null
     */
    public function issueApiToken(string $login, string $password): ?array
    {
        $user = User::query()->where($this->loginField($login), $login)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    /**
     * @param  array{name: string, username: string, email: string, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function registerWithApiToken(array $data): array
    {
        $user = $this->register($data);

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    private function loginField(string $login): string
    {
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }
}
