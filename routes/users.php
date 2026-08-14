<?php

declare(strict_types=1);

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('users')->name('users.')->group(function (): void {
    Route::get('', [UserController::class, 'index'])->middleware('permission:users.view')->name('index');
    Route::get('data', [UserController::class, 'data'])->middleware('permission:users.view')->name('data');
    Route::get('check-username', [UserController::class, 'checkUsername'])->middleware('permission:users.view')->name('check-username');
    Route::post('', [UserController::class, 'store'])->middleware('permission:users.create')->name('store');
    Route::put('{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('update');
    Route::delete('{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('destroy');
});
