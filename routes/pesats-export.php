<?php

declare(strict_types=1);

use App\Http\Controllers\PesatsExport\PermohonanController;
use App\Http\Controllers\PesatsExport\PermohonanVerifikasiController;
use App\Http\Controllers\PesatsExport\SatsLnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('pesats-export')->name('pesats-export.')->group(function (): void {
    Route::get('kuota-perusahaan-options/{source?}', [SatsLnController::class, 'kuotaPerusahaanOptions'])->name('kuota-perusahaan-options');

    Route::get('', [PermohonanController::class, 'index'])->middleware('permission:permohonan.view')->name('index');
    Route::post('', [PermohonanController::class, 'store'])->middleware('permission:permohonan.create')->name('store');

    // Static "verifikasi" prefix must be registered before the catch-all "{permohonan}" routes
    // below, otherwise Laravel would try to implicit-bind "verifikasi" itself as a Permohonan id.
    Route::prefix('verifikasi')->name('verifikasi.')->middleware('permission:permohonan.verify')->group(function (): void {
        Route::get('', [PermohonanVerifikasiController::class, 'index'])->name('index');
        Route::get('{permohonan}', [PermohonanVerifikasiController::class, 'show'])->name('show');
        Route::post('{permohonan}/verify', [PermohonanVerifikasiController::class, 'verify'])->name('verify');
        Route::post('{permohonan}/reject', [PermohonanVerifikasiController::class, 'reject'])->name('reject');
        Route::post('{permohonan}/sats-ln/{satsLn}/mark-printed', [PermohonanVerifikasiController::class, 'markPrinted'])->name('mark-printed');
        Route::post('{permohonan}/sats-ln/{satsLn}/mark-signed', [PermohonanVerifikasiController::class, 'markSigned'])->name('mark-signed');
    });

    Route::get('{permohonan}', [PermohonanController::class, 'show'])->middleware('permission:permohonan.view')->name('show');
    Route::get('{permohonan}/edit', [PermohonanController::class, 'edit'])->middleware('permission:permohonan.create')->name('edit');
    Route::post('{permohonan}/submit', [PermohonanController::class, 'submit'])->middleware('permission:permohonan.create')->name('submit');
    Route::delete('{permohonan}', [PermohonanController::class, 'destroy'])->middleware('permission:permohonan.create')->name('destroy');

    Route::middleware('permission:permohonan.create')->group(function (): void {
        Route::post('{permohonan}/sats-ln', [SatsLnController::class, 'store'])->name('sats-ln.store');
        Route::put('{permohonan}/sats-ln/{satsLn}', [SatsLnController::class, 'update'])->name('sats-ln.update');
        Route::delete('{permohonan}/sats-ln/{satsLn}', [SatsLnController::class, 'destroy'])->name('sats-ln.destroy');
    });
});
