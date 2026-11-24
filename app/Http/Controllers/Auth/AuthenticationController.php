<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\KomoditasEntitas;
use App\Models\User;
use App\Services\Auth\AuthenticationService;
use App\Services\KomoditasEntitasService;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticationController extends Controller
{
    private const SESSION_OTP_USER_ID = 'login_otp_user_id';

    private const SESSION_OTP_REMEMBER = 'login_otp_remember';

    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly OnboardingService $onboardingService,
        private readonly KomoditasEntitasService $komoditasEntitasService,
    ) {}

    public function showLogin(): View
    {
        return view('auth._modern-login');
    }

    /**
     * Langkah 1 dari 2: cek kredensial + penanda "sesi aktif di device lain" - BELUM login
     * (Auth::login belum dipanggil sama sekali di sini). Kalau lolos, kirim OTP dan alihkan ke
     * halaman verifikasi - login baru benar-benar selesai di verifyOtp() - lihat claude/auth-task.md.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $user = $this->authenticationService->validateCredentials($credentials['login'], $credentials['password']);

        if (! $user) {
            throw ValidationException::withMessages([
                'login' => __('Username/email atau password yang Anda masukkan salah.'),
            ]);
        }

        if ($this->authenticationService->hasActiveSessionElsewhere($user)) {
            return redirect()->route('login')->with(
                'error',
                __('Akun ini sedang digunakan di perangkat lain. Silakan coba lagi nanti atau logout dari perangkat tersebut.'),
            );
        }

        $this->authenticationService->sendOtp($user);

        $request->session()->put(self::SESSION_OTP_USER_ID, $user->id);
        $request->session()->put(self::SESSION_OTP_REMEMBER, $request->boolean('remember'));

        return redirect()->route('login.otp.show');
    }

    public function showOtp(Request $request): View|RedirectResponse
    {
        $user = $this->pendingOtpUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        return view('auth._modern-two-steps', [
            'mode' => 'otp',
            'maskedEmail' => $this->maskEmail($user->email),
        ]);
    }

    public function verifyOtp(OtpVerifyRequest $request): RedirectResponse
    {
        $user = $this->pendingOtpUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $this->authenticationService->verifyOtp($user, $request->validated('otp'))) {
            throw ValidationException::withMessages([
                'otp' => __('Kode OTP tidak valid atau sudah kedaluwarsa.'),
            ]);
        }

        $remember = (bool) $request->session()->pull(self::SESSION_OTP_REMEMBER, false);
        $request->session()->forget(self::SESSION_OTP_USER_ID);

        $this->authenticationService->completeLogin($user, $request, $remember);

        if (! $user->onboarding_completed) {
            return redirect()->to($this->onboardingService->nextIncompleteStepRoute($user));
        }

        $this->flashExpiringKomoditasAlert($user);

        // Login baru selalu mendarat di dashboard; kembali ke halaman terakhir hanya untuk alur
        // input ulang password setelah idle (ReauthenticateController).
        $request->session()->forget('url.intended');

        return redirect()->route('dashboards.index');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $user = $this->pendingOtpUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        $this->authenticationService->sendOtp($user);

        return redirect()->route('login.otp.show')
            ->with('success', __('Kode OTP baru telah dikirim ke email Anda.'));
    }

    private function pendingOtpUser(Request $request): ?User
    {
        $userId = $request->session()->get(self::SESSION_OTP_USER_ID);

        if ($userId === null) {
            return null;
        }

        return User::find($userId);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2) + [1 => ''];

        $visible = mb_substr($local, 0, 2);

        return $visible.str_repeat('*', max(mb_strlen($local) - 2, 1)).'@'.$domain;
    }

    /**
     * Dicek ulang SETIAP kali login berhasil (bukan cuma sekali) sesuai permintaan -
     * lihat claude/status-cek-task.md. Flash session cuma bertahan sampai request
     * berikutnya (perilaku bawaan Laravel), dibaca oleh <x-komoditas-entitas-expiry-alert/>
     * yang di-include global lewat layouts/partials/flash-dialog.blade.php.
     */
    private function flashExpiringKomoditasAlert(User $user): void
    {
        $entity = $user->entity;

        if ($entity === null) {
            return;
        }

        $expiring = $this->komoditasEntitasService->expiringWithin($entity);

        if ($expiring->isEmpty()) {
            return;
        }

        session()->flash('komoditas_entitas_expiring_alert', $expiring->map(fn (KomoditasEntitas $item) => [
            'komoditas' => $item->komoditas?->name,
            'tanggal_berakhir' => $item->tanggal_berakhir->translatedFormat('d F Y'),
        ])->all());
    }

    public function showRegister(): View
    {
        return view('auth._modern-register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->authenticationService->register($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->to(route('onboarding.type.show'));
    }

    public function logout(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authenticationService->releaseSessionLock($user);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
