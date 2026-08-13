<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticationController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthenticationController::class, 'login']);

    Route::get('register', [AuthenticationController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthenticationController::class, 'register']);
});

Route::post('logout', [AuthenticationController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
