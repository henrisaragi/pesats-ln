<?php

declare(strict_types=1);

use App\Http\Controllers\Onboarding\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function (): void {
    Route::prefix('jenis-entitas')->name('type.')->group(function (): void {
        Route::get('', [OnboardingController::class, 'showType'])->name('show');
        Route::post('', [OnboardingController::class, 'storeType'])->name('store');
    });

    Route::prefix('data-entitas')->name('entitas.')->group(function (): void {
        Route::get('', [OnboardingController::class, 'showEntitas'])->name('show');
        Route::post('', [OnboardingController::class, 'storeEntitas'])->name('store');
    });

    Route::prefix('detail')->name('detail.')->group(function (): void {
        Route::get('', [OnboardingController::class, 'showDetail'])->name('show');
        Route::post('', [OnboardingController::class, 'storeDetail'])->name('store');
    });

    Route::get('selesai', [OnboardingController::class, 'showComplete'])->name('complete.show');
});
