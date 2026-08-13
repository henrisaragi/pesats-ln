<?php

declare(strict_types=1);

use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('permissions')->name('permissions.')->group(function (): void {
    Route::get('', [PermissionController::class, 'index'])->middleware('permission:permissions.view')->name('index');
    Route::post('', [PermissionController::class, 'store'])->middleware('permission:permissions.create')->name('store');
    Route::put('{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.edit')->name('update');
    Route::delete('{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete')->name('destroy');
});
