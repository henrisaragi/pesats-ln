<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('api.auth.')->group(function (): void {
    Route::post('login', [AuthenticationController::class, 'login'])->name('login');
    Route::post('register', [AuthenticationController::class, 'register'])->name('register');
});
