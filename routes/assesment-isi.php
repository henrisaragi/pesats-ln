<?php

declare(strict_types=1);

use App\Http\Controllers\AssesmentIsi\AssesmentIsiController;
use App\Http\Controllers\AssesmentIsi\AssesmentIsiVerifikasiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('assesment-isi')->name('assesment-isi.')->group(function (): void {
    Route::get('', [AssesmentIsiController::class, 'index'])->middleware('permission:assesment-isi.view')->name('index');
    Route::post('', [AssesmentIsiController::class, 'store'])->middleware('permission:assesment-isi.create')->name('store');

    // Route literal (bukti/, verifikasi/) didaftarkan sebelum wildcard {isi} di bawah supaya tidak
    // tertangkap sebagai id AssesmentIsi (segment tunggal sama seperti "assesment-isi/{isi}").
    Route::get('bukti/{bukti}/preview', [AssesmentIsiController::class, 'previewBukti'])->middleware('permission:assesment-isi.view|assesment-isi.verify|assesment-isi.view-all')->name('bukti.preview');

    Route::prefix('verifikasi')->name('verifikasi.')->group(function (): void {
        Route::get('', [AssesmentIsiVerifikasiController::class, 'index'])->middleware('permission:assesment-isi.verify')->name('index');
        Route::get('{isi}', [AssesmentIsiVerifikasiController::class, 'show'])->middleware('permission:assesment-isi.verify')->name('show');
        Route::post('{isi}/jawaban/{pertanyaan}', [AssesmentIsiVerifikasiController::class, 'saveJawaban'])->middleware('permission:assesment-isi.verify')->name('jawaban');
        Route::post('{isi}/complete', [AssesmentIsiVerifikasiController::class, 'complete'])->middleware('permission:assesment-isi.verify')->name('complete');
    });

    Route::get('{isi}', [AssesmentIsiController::class, 'show'])->middleware('permission:assesment-isi.view')->name('show');
    // POST (bukan PUT) - "Simpan Draft" dan "Submit Assesment" adalah SATU form dengan tombol
    // formaction berbeda (lihat assesment-isi/show.blade.php); PUT butuh spoofing _method yang
    // akan ikut membelokkan tombol "Submit" (formaction ke rute submit) jadi request PUT juga.
    Route::post('{isi}', [AssesmentIsiController::class, 'update'])->middleware('permission:assesment-isi.create')->name('update');
    Route::get('{isi}/surat-pernyataan', [AssesmentIsiController::class, 'previewSuratPernyataan'])->middleware('permission:assesment-isi.view|assesment-isi.verify|assesment-isi.view-all')->name('surat-pernyataan.preview');
    Route::post('{isi}/submit', [AssesmentIsiController::class, 'submit'])->middleware('permission:assesment-isi.create')->name('submit');
    Route::post('{isi}/bukti/{buktiDukungId}', [AssesmentIsiController::class, 'uploadBukti'])->middleware('permission:assesment-isi.create')->name('bukti.upload');
});
