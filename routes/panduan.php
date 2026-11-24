<?php

declare(strict_types=1);

use App\Http\Controllers\Panduan\TemplateSuratPernyataanController;
use App\Http\Controllers\Panduan\VideoPanduanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::prefix('template-surat-pernyataan')->name('template-surat-pernyataan.')->group(function (): void {
        Route::get('', [TemplateSuratPernyataanController::class, 'index'])->middleware('permission:template-surat-pernyataan.view')->name('index');
        Route::post('', [TemplateSuratPernyataanController::class, 'store'])->middleware('permission:template-surat-pernyataan.create')->name('store');
        Route::put('{template}', [TemplateSuratPernyataanController::class, 'update'])->middleware('permission:template-surat-pernyataan.update')->name('update');
        Route::delete('{template}', [TemplateSuratPernyataanController::class, 'destroy'])->middleware('permission:template-surat-pernyataan.delete')->name('destroy');
        Route::get('{template}/download', [TemplateSuratPernyataanController::class, 'download'])->name('download');
        Route::get('{template}/preview', [TemplateSuratPernyataanController::class, 'preview'])->name('preview');
    });

    Route::prefix('video-panduan')->name('video-panduan.')->group(function (): void {
        Route::get('', [VideoPanduanController::class, 'index'])->middleware('permission:video-panduan.view')->name('index');
        Route::post('', [VideoPanduanController::class, 'store'])->middleware('permission:video-panduan.create')->name('store');
        Route::put('{video}', [VideoPanduanController::class, 'update'])->middleware('permission:video-panduan.update')->name('update');
        Route::delete('{video}', [VideoPanduanController::class, 'destroy'])->middleware('permission:video-panduan.delete')->name('destroy');
        Route::get('{video}/stream', [VideoPanduanController::class, 'stream'])->name('stream');
    });
});
