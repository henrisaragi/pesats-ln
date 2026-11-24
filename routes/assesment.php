<?php

declare(strict_types=1);

use App\Http\Controllers\Assesment\AssesmentAspekController;
use App\Http\Controllers\Assesment\AssesmentController;
use App\Http\Controllers\Assesment\AssesmentPertanyaanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('assesment')->name('assesment.')->group(function (): void {
    Route::get('', [AssesmentController::class, 'index'])->middleware('permission:assesment.view')->name('index');
    Route::post('', [AssesmentController::class, 'store'])->middleware('permission:assesment.create')->name('store');
    Route::put('{assesment}', [AssesmentController::class, 'update'])->middleware('permission:assesment.update')->name('update');
    Route::delete('{assesment}', [AssesmentController::class, 'destroy'])->middleware('permission:assesment.delete')->name('destroy');

    Route::get('aspek/search', [AssesmentAspekController::class, 'search'])->middleware('permission:assesment.create')->name('aspek.search');

    Route::prefix('{assesment}/pertanyaan')->name('pertanyaan.')->group(function (): void {
        Route::get('', [AssesmentPertanyaanController::class, 'index'])->middleware('permission:assesment.view')->name('index');
        Route::post('', [AssesmentPertanyaanController::class, 'store'])->middleware('permission:assesment.create')->name('store');
        Route::get('export', [AssesmentPertanyaanController::class, 'export'])->middleware('permission:assesment.view')->name('export');
        Route::post('import', [AssesmentPertanyaanController::class, 'import'])->middleware('permission:assesment.create')->name('import');
        Route::get('{pertanyaan}/edit', [AssesmentPertanyaanController::class, 'edit'])->middleware('permission:assesment.update')->name('edit');
        Route::put('{pertanyaan}', [AssesmentPertanyaanController::class, 'update'])->middleware('permission:assesment.update')->name('update');
        Route::delete('{pertanyaan}', [AssesmentPertanyaanController::class, 'destroy'])->middleware('permission:assesment.delete')->name('destroy');
    });
});
