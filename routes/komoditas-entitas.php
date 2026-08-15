<?php

declare(strict_types=1);

use App\Http\Controllers\KomoditasEntitas\KomoditasEntitasController;
use App\Http\Controllers\KomoditasEntitas\KomoditasEntitasVerifikasiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('komoditas-entitas')->name('komoditas-entitas.')->group(function (): void {
    Route::get('', [KomoditasEntitasController::class, 'index'])->middleware('permission:komoditas-entitas.view')->name('index');
    Route::post('', [KomoditasEntitasController::class, 'store'])->middleware('permission:komoditas-entitas.create')->name('store');
    Route::get('{komoditasEntitas}/download', [KomoditasEntitasController::class, 'download'])->middleware('permission:komoditas-entitas.view|komoditas-entitas.verify')->name('download');
    Route::get('{komoditasEntitas}/preview', [KomoditasEntitasController::class, 'preview'])->middleware('permission:komoditas-entitas.view|komoditas-entitas.verify')->name('preview');

    Route::prefix('verifikasi')->name('verifikasi.')->group(function (): void {
        Route::get('', [KomoditasEntitasVerifikasiController::class, 'index'])->middleware('permission:komoditas-entitas.verify')->name('index');
        Route::post('{komoditasEntitas}/verify', [KomoditasEntitasVerifikasiController::class, 'verify'])->middleware('permission:komoditas-entitas.verify')->name('verify');
        Route::post('{komoditasEntitas}/reject', [KomoditasEntitasVerifikasiController::class, 'reject'])->middleware('permission:komoditas-entitas.verify')->name('reject');
    });
});
