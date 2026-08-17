<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\AuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idle timeout (lihat claude/auth-task.md): kalau tidak ada aktivitas lebih dari
 * AuthenticationService::IDLE_TIMEOUT_MINUTES, user dilogout paksa dan diarahkan ke halaman
 * input ulang password (TANPA OTP lagi, lihat App\Http\Controllers\Auth\ReauthenticateController).
 * Selain itu, last_activity_at di-refresh tiap request - dipakai juga oleh
 * AuthenticationService::hasActiveSessionElsewhere() supaya penanda "sesi aktif di device lain"
 * (dicek SEKALI di titik login, lihat AuthenticationController::login()) ikut basi kalau device
 * itu sendiri sudah lama tidak dipakai.
 *
 * SENGAJA TIDAK membandingkan current_session_id user dengan session()->getId() request ini
 * (pernah dicoba, dibatalkan) - "satu sesi aktif" di app ini ditegakkan dengan MENOLAK login BARU
 * selama device lain masih aktif (lihat hasActiveSessionElsewhere()), bukan dengan menendang sesi
 * LAMA di tengah jalan. Membandingkan session id per-request ternyata rapuh: Laravel test client
 * tidak meneruskan cookie sesi antar panggilan HTTP terpisah dalam satu test (beda dari browser
 * sungguhan), jadi setiap test yang melakukan >1 request terautentikasi berturut-turut akan
 * salah terdeteksi sebagai "device lain" dan force-logout di tengah test.
 */
class EnsureAuthSessionIsFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->last_activity_at !== null
            && $user->last_activity_at->lt(now()->subMinutes(AuthenticationService::IDLE_TIMEOUT_MINUTES))
        ) {
            return $this->forceReauthenticate($request, $user);
        }

        User::withoutTimestamps(function () use ($user): void {
            $user->forceFill(['last_activity_at' => now()])->save();
        });

        return $next($request);
    }

    private function forceReauthenticate(Request $request, User $user): Response
    {
        $userId = $user->id;
        $intendedUrl = $request->fullUrl();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->put('reauth_user_id', $userId);
        $request->session()->put('reauth_intended_url', $intendedUrl);

        return redirect()->route('reauthenticate.show');
    }
}
