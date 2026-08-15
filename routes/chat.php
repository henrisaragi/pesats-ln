<?php

declare(strict_types=1);

use App\Http\Controllers\Chat\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:chat.start|chat.respond'])->prefix('chat')->name('chat.')->group(function (): void {
    Route::get('', [ChatController::class, 'index'])->name('index');
    Route::get('list', [ChatController::class, 'list'])->name('list');
    Route::post('', [ChatController::class, 'store'])->middleware('permission:chat.start')->name('store');
    Route::get('{conversation}/messages', [ChatController::class, 'messages'])->name('messages');
    Route::post('{conversation}/messages', [ChatController::class, 'send'])->name('send');
    Route::post('{conversation}/end', [ChatController::class, 'end'])->name('end');
});
