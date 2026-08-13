<?php

declare(strict_types=1);

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('roles')->name('roles.')->group(function (): void {
    Route::get('', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('index');
    Route::post('', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('store');
    Route::put('{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('update');
    Route::delete('{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('destroy');
});
