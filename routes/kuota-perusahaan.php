<?php

declare(strict_types=1);

use App\Http\Controllers\KuotaPerusahaan\KuotaPerusahaanController;
use App\Http\Controllers\KuotaPerusahaan\KuotaSayaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->get('kuota-saya', [KuotaSayaController::class, 'index'])
    ->middleware('permission:kuota-saya.view')
    ->name('kuota-saya.index');

Route::middleware('auth')->prefix('kuota-perusahaan')->name('kuota-perusahaan.')->group(function (): void {
    Route::get('komoditas-options/{entity}', [KuotaPerusahaanController::class, 'komoditasOptions'])->name('komoditas-options');
    Route::get('source-status-options/{komoditas}/{year}', [KuotaPerusahaanController::class, 'sourceStatusOptions'])->name('source-status-options');
    Route::get('trade-code-options/{komoditas}/{source}/{year}', [KuotaPerusahaanController::class, 'tradeCodeOptions'])->name('trade-code-options');
    Route::get('species-options/{komoditas}/{source}/{tradeCode}/{year}', [KuotaPerusahaanController::class, 'speciesOptions'])->name('species-options');

    Route::get('', [KuotaPerusahaanController::class, 'index'])->middleware('permission:kuota-perusahaan.view')->name('index');
    Route::post('', [KuotaPerusahaanController::class, 'store'])->middleware('permission:kuota-perusahaan.create')->name('store');
    Route::put('{kuotaPerusahaan}', [KuotaPerusahaanController::class, 'update'])->middleware('permission:kuota-perusahaan.update')->name('update');
    Route::delete('{kuotaPerusahaan}', [KuotaPerusahaanController::class, 'destroy'])->middleware('permission:kuota-perusahaan.delete')->name('destroy');
});
