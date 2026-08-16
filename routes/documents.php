<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('documents')->name('documents.')->group(function (): void {
    Route::get('permohonan-options/{entity}', [DocumentController::class, 'permohonanOptions'])->name('permohonan-options');
    Route::get('sats-ln-options/{permohonan}', [DocumentController::class, 'satsLnOptions'])->name('sats-ln-options');

    Route::get('', [DocumentController::class, 'index'])->middleware('permission:documents.view')->name('index');
    Route::post('', [DocumentController::class, 'store'])->middleware('permission:documents.create')->name('store');
    Route::put('{document}', [DocumentController::class, 'update'])->middleware('permission:documents.update')->name('update');
    Route::delete('{document}', [DocumentController::class, 'destroy'])->middleware('permission:documents.delete')->name('destroy');
    Route::get('{document}/download', [DocumentController::class, 'download'])->middleware('permission:documents.view')->name('download');
    Route::get('{document}/preview', [DocumentController::class, 'preview'])->middleware('permission:documents.view')->name('preview');
});
