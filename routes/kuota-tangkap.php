<?php

declare(strict_types=1);

use App\Http\Controllers\KuotaTangkap\KuotaAllocationController;
use App\Http\Controllers\KuotaTangkap\KuotaExportController;
use App\Http\Controllers\KuotaTangkap\KuotaPeriodController;
use App\Http\Controllers\KuotaTangkap\KuotaRealisasiController;
use App\Http\Controllers\KuotaTangkap\KuotaSayaTangkapController;
use App\Http\Controllers\KuotaTangkap\KuotaSettingController;
use App\Http\Controllers\KuotaTangkap\KuotaWorkflowController;
use Illuminate\Support\Facades\Route;

/*
 * Pembagian Kuota Ekspor - docs/PRD-pembagian-kuota-tangkap.md. Middleware permission = gerbang
 * kasar; aturan status, wilayah, dan pemisahan pengaju/penyetuju ada di KuotaPeriodPolicy.
 */
Route::middleware('auth')->get('kuota-tangkap-saya', [KuotaSayaTangkapController::class, 'index'])
    ->middleware('permission:kuota.view-own')
    ->name('kuota-tangkap-saya.index');

Route::middleware('auth')->prefix('kuota-tangkap')->name('kuota-tangkap.')->group(function (): void {
    Route::get('pengaturan', [KuotaSettingController::class, 'edit'])->middleware('permission:kuota.settings')->name('settings.edit');
    Route::put('pengaturan', [KuotaSettingController::class, 'update'])->middleware('permission:kuota.settings')->name('settings.update');

    Route::get('', [KuotaPeriodController::class, 'index'])->middleware('permission:kuota.view')->name('index');
    Route::post('', [KuotaPeriodController::class, 'store'])->middleware('permission:kuota.create')->name('store');

    Route::scopeBindings()->prefix('{period}')->group(function (): void {
        Route::get('', [KuotaPeriodController::class, 'show'])->middleware('permission:kuota.view')->name('show');
        Route::put('', [KuotaPeriodController::class, 'update'])->middleware('permission:kuota.update')->name('update');
        Route::delete('', [KuotaPeriodController::class, 'destroy'])->middleware('permission:kuota.delete')->name('destroy');

        Route::post('peserta', [KuotaAllocationController::class, 'store'])->middleware('permission:kuota.update')->name('peserta.store');
        Route::post('peserta/kandidat', [KuotaAllocationController::class, 'candidates'])->middleware('permission:kuota.update')->name('peserta.candidates');
        Route::put('peserta/{allocation}', [KuotaAllocationController::class, 'updatePermohonan'])->middleware('permission:kuota.update')->name('peserta.update');
        Route::delete('peserta/{allocation}', [KuotaAllocationController::class, 'destroy'])->middleware('permission:kuota.update')->name('peserta.destroy');

        Route::post('hitung', [KuotaWorkflowController::class, 'calculate'])->middleware('permission:kuota.calculate')->name('calculate');
        Route::put('alokasi', [KuotaAllocationController::class, 'update'])->middleware('permission:kuota.allocate')->name('alokasi.update');
        Route::post('alokasi/salin-rekomendasi', [KuotaAllocationController::class, 'copyRecommendation'])->middleware('permission:kuota.allocate')->name('alokasi.copy');
        Route::post('ajukan', [KuotaWorkflowController::class, 'submit'])->middleware('permission:kuota.submit')->name('submit');
        Route::post('setujui', [KuotaWorkflowController::class, 'approve'])->middleware('permission:kuota.approve')->name('approve');
        Route::post('kembalikan', [KuotaWorkflowController::class, 'return'])->middleware('permission:kuota.approve')->name('return');
        Route::post('tutup', [KuotaWorkflowController::class, 'close'])->middleware('permission:kuota.approve')->name('close');

        Route::post('realisasi/{allocation}', [KuotaRealisasiController::class, 'update'])->middleware('permission:kuota.realisasi')->name('realisasi.update');
        Route::get('realisasi/{allocation}/sk', [KuotaRealisasiController::class, 'downloadSk'])->middleware('permission:kuota.view|kuota.view-own')->name('realisasi.sk');

        Route::get('snapshot/{snapshot}', [KuotaPeriodController::class, 'snapshot'])->middleware('permission:kuota.view')->name('snapshot');
        Route::get('export/csv', [KuotaExportController::class, 'csv'])->middleware('permission:kuota.export')->name('export.csv');
        Route::get('export/pdf', [KuotaExportController::class, 'pdf'])->middleware('permission:kuota.export')->name('export.pdf');
    });
});
