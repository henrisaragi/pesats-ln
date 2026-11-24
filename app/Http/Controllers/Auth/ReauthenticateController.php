<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ReauthenticateRequest;
use App\Models\User;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Input ulang password setelah sesi idle 1 jam (lihat App\Http\Middleware\EnsureAuthSessionIsFresh)
 * - sengaja TIDAK lewat OTP lagi (beda dari login normal, lihat AuthenticationController), karena
 * ini bukan login dari perangkat baru - hanya membuktikan ulang identitas di perangkat yang sama
 * yang sesinya baru saja kedaluwarsa. Lihat claude/auth-task.md.
 */
class ReauthenticateController extends Controller
{
    private const SESSION_USER_ID = 'reauth_user_id';

    private const SESSION_INTENDED_URL = 'reauth_intended_url';

    public function __construct(
        private readonly AuthenticationService $authenticationService,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->pendingReauthUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        return view('auth._modern-two-steps', [
            'mode' => 'password',
            'reauthUserName' => $user->name,
        ]);
    }

    public function store(ReauthenticateRequest $request): RedirectResponse
    {
        $user = $this->pendingReauthUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('Password yang Anda masukkan salah.'),
            ]);
        }

        $intendedUrl = $request->session()->pull(self::SESSION_INTENDED_URL);
        $request->session()->forget(self::SESSION_USER_ID);

        $this->authenticationService->completeLogin($user, $request);

        return $intendedUrl !== null
            ? redirect()->to($intendedUrl)
            : redirect()->intended(route('dashboards.index'));
    }

    private function pendingReauthUser(Request $request): ?User
    {
        // Fallback ke cookie saat sesi server sudah hilang total (kedaluwarsa/dibersihkan), bukan
        // hanya idle - lihat AuthenticationService::REAUTH_COOKIE.
        $userId = $request->session()->get(self::SESSION_USER_ID)
            ?? $request->cookie(AuthenticationService::REAUTH_COOKIE);

        if ($userId === null) {
            return null;
        }

        return User::find($userId);
    }
}
