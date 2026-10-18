<?php

declare(strict_types=1);

use App\Http\Controllers\PermohonanExport\PermohonanController;
use App\Http\Controllers\PermohonanExport\PermohonanVerifikasiController;
use App\Http\Controllers\PermohonanExport\SatsLnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('permohonan-export')->name('permohonan-export.')->group(function (): void {
    Route::get('kuota-perusahaan-options/{source?}', [SatsLnController::class, 'kuotaPerusahaanOptions'])->name('kuota-perusahaan-options');
    Route::get('port-options/{country}', [SatsLnController::class, 'portOptions'])->name('port-options');
    Route::get('item-komoditas-options/{source}', [SatsLnController::class, 'itemKomoditasOptions'])->name('item-komoditas-options');
    Route::get('item-trade-code-options/{source}/{komoditas}', [SatsLnController::class, 'itemTradeCodeOptions'])->name('item-trade-code-options');
    Route::get('item-species-options/{source}/{komoditas}/{tradeCode}', [SatsLnController::class, 'itemSpeciesOptions'])->name('item-species-options');

    Route::get('', [PermohonanController::class, 'index'])->middleware('permission:permohonan-export.view')->name('index');
    Route::post('', [PermohonanController::class, 'store'])->middleware('permission:permohonan-export.create')->name('store');

    // Static "verifikasi" prefix must be registered before the catch-all "{permohonan}" routes
    // below, otherwise Laravel would try to implicit-bind "verifikasi" itself as a Permohonan id.
    Route::prefix('verifikasi')->name('verifikasi.')->group(function (): void {
        Route::get('', [PermohonanVerifikasiController::class, 'index'])->middleware('permission:permohonan.verify.view')->name('index');
        // Halaman detail (show/sats-ln.show) digerbang permission-nya sendiri (permohonan.verify-
        // detail), terpisah dari permohonan.verify.view (index) dan permohonan-export.verify
        // (khusus aksi tombol Verifikasi) - supaya akses lihat daftar, lihat detail, dan
        // melakukan aksi verifikasi bisa diatur independen satu sama lain.
        Route::get('saya', [PermohonanVerifikasiController::class, 'mine'])->middleware('permission:permohonan-export.verify')->name('mine');
        Route::get('sats-ln-number-check', [PermohonanVerifikasiController::class, 'checkSatsLnNumber'])->middleware('permission:permohonan-export.verify')->name('sats-ln.number-check');
        Route::get('{permohonan}', [PermohonanVerifikasiController::class, 'show'])->middleware('permission:permohonan.verify-detail')->name('show');
        Route::get('{permohonan}/sats-ln/{satsLn}', [PermohonanVerifikasiController::class, 'show'])->middleware('permission:permohonan.verify-detail')->name('sats-ln.show');
        Route::get('{permohonan}/sats-ln/{satsLn}/documents/{document}/preview', [PermohonanVerifikasiController::class, 'previewDocument'])->middleware('permission:permohonan.verify-detail')->name('sats-ln.documents.preview');
        Route::get('{permohonan}/sats-ln/{satsLn}/documents/{document}/download', [PermohonanVerifikasiController::class, 'downloadDocument'])->middleware('permission:permohonan.verify-detail')->name('sats-ln.documents.download');
        Route::post('{permohonan}/disposisi', [PermohonanVerifikasiController::class, 'disposisi'])->middleware('permission:permohonan-export.disposisi')->name('disposisi');
        Route::post('{permohonan}/disposisi-cetak', [PermohonanVerifikasiController::class, 'disposisiCetak'])->middleware('permission:permohonan-export.disposisi-cetak')->name('disposisi-cetak');
        Route::post('{permohonan}/verify', [PermohonanVerifikasiController::class, 'verify'])->middleware('permission:permohonan-export.verify')->name('verify');
        Route::post('{permohonan}/sats-ln/{satsLn}/verify', [PermohonanVerifikasiController::class, 'verifySatsLn'])->middleware('permission:permohonan-export.verify')->name('sats-ln.verify');
        Route::post('{permohonan}/sats-ln/{satsLn}/reopen', [PermohonanVerifikasiController::class, 'reopenSatsLn'])->middleware('permission:permohonan-export.verify')->name('sats-ln.reopen');
        Route::post('{permohonan}/reject', [PermohonanVerifikasiController::class, 'reject'])->middleware('permission:permohonan-export.verify')->name('reject');
        Route::post('{permohonan}/sats-ln/{satsLn}/mark-printed', [PermohonanVerifikasiController::class, 'markPrinted'])->middleware('permission:permohonan-export.verify')->name('mark-printed');
        Route::post('{permohonan}/sats-ln/{satsLn}/mark-signed', [PermohonanVerifikasiController::class, 'markSigned'])->middleware('permission:permohonan-export.verify')->name('mark-signed');
    });

    Route::get('{permohonan}', [PermohonanController::class, 'show'])->middleware('permission:permohonan-export.view')->name('show');
    Route::get('{permohonan}/sats-ln/{satsLn}', [PermohonanController::class, 'show'])->middleware('permission:permohonan-export.view')->name('sats-ln.show');
    Route::get('{permohonan}/edit', [PermohonanController::class, 'edit'])->middleware('permission:permohonan-export.edit')->name('edit');
    Route::post('{permohonan}/submit', [PermohonanController::class, 'submit'])->middleware('permission:permohonan-export.edit')->name('submit');
    Route::delete('{permohonan}', [PermohonanController::class, 'destroy'])->middleware('permission:permohonan-export.delete')->name('destroy');

    Route::middleware('permission:permohonan-export.edit')->group(function (): void {
        Route::post('{permohonan}/sats-ln', [SatsLnController::class, 'store'])->name('sats-ln.store');
        // Segmen statis "submit" harus didaftarkan sebelum "{satsLn}" di bawah, sama seperti
        // alasan prefix "verifikasi" di atas - kalau tidak, Laravel akan coba implicit-bind
        // "submit" sebagai SatsLn id.
        Route::post('{permohonan}/sats-ln/submit', [PermohonanController::class, 'submitSatsLn'])->name('sats-ln.submit');
        Route::put('{permohonan}/sats-ln/{satsLn}', [SatsLnController::class, 'update'])->name('sats-ln.update');
        Route::delete('{permohonan}/sats-ln/{satsLn}', [SatsLnController::class, 'destroy'])->name('sats-ln.destroy');
        Route::post('{permohonan}/sats-ln/{satsLn}/items', [SatsLnController::class, 'storeItem'])->name('sats-ln.items.store');
        Route::put('{permohonan}/sats-ln/{satsLn}/items/{item}', [SatsLnController::class, 'updateItem'])->name('sats-ln.items.update');
        Route::post('{permohonan}/sats-ln/{satsLn}/documents', [SatsLnController::class, 'attachDocument'])->name('sats-ln.documents.attach');
    });
});
