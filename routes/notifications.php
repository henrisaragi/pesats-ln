<?php

declare(strict_types=1);

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function (): void {
    Route::get('', [NotificationController::class, 'index'])->name('index');
    Route::post('read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::post('{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
});
