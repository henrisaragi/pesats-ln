<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\WhatsAppSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('settings')->name('settings.')->group(function (): void {
    Route::prefix('whatsapp')->name('whatsapp.')->middleware('permission:settings.whatsapp.manage')->group(function (): void {
        Route::get('', [WhatsAppSettingController::class, 'index'])->name('index');
        Route::put('', [WhatsAppSettingController::class, 'update'])->name('update');
    });
});
