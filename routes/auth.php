<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Auth\ReauthenticateController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticationController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthenticationController::class, 'login']);

    Route::get('login/otp', [AuthenticationController::class, 'showOtp'])->name('login.otp.show');
    Route::post('login/otp', [AuthenticationController::class, 'verifyOtp'])
        ->middleware('throttle:6,1')
        ->name('login.otp.verify');
    Route::post('login/otp/resend', [AuthenticationController::class, 'resendOtp'])
        ->middleware('throttle:3,1')
        ->name('login.otp.resend');

    Route::get('register', [AuthenticationController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthenticationController::class, 'register']);
});

Route::post('logout', [AuthenticationController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Input ulang password setelah sesi idle 1 jam - bukan halaman "guest" biasa (cuma bisa diakses
// lewat redirect dari EnsureAuthSessionIsFresh yang menaruh reauth_user_id di sesi), dan bukan
// halaman "auth" biasa juga (user pada titik ini sudah dilogout paksa) - lihat claude/auth-task.md.
Route::get('reauthenticate', [ReauthenticateController::class, 'show'])->name('reauthenticate.show');
Route::post('reauthenticate', [ReauthenticateController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('reauthenticate.store');
