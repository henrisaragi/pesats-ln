<?php

declare(strict_types=1);

use App\Http\Controllers\Entitas\EntitasController;
use App\Http\Controllers\Master\CountryController;
use App\Http\Controllers\Master\KecamatanController;
use App\Http\Controllers\Master\KelurahanController;
use App\Http\Controllers\Master\KomoditasController;
use App\Http\Controllers\Master\KotaController;
use App\Http\Controllers\Master\PortController;
use App\Http\Controllers\Master\ProvinsiController;
use App\Http\Controllers\Master\SourceStatusController;
use App\Http\Controllers\Master\SpeciesController;
use App\Http\Controllers\Master\TradeCodeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('master')->name('master.')->group(function (): void {

    Route::prefix('countries')->name('countries.')->group(function (): void {
        Route::get('', [CountryController::class, 'index'])->middleware('permission:countries.view')->name('index');
        Route::post('', [CountryController::class, 'store'])->middleware('permission:countries.create')->name('store');
        Route::put('{country}', [CountryController::class, 'update'])->middleware('permission:countries.update')->name('update');
        Route::delete('{country}', [CountryController::class, 'destroy'])->middleware('permission:countries.delete')->name('destroy');
    });

    Route::prefix('provinsi')->name('provinsi.')->group(function (): void {
        Route::get('', [ProvinsiController::class, 'index'])->middleware('permission:provinsi.view')->name('index');
        Route::post('', [ProvinsiController::class, 'store'])->middleware('permission:provinsi.create')->name('store');
        Route::put('{provinsi}', [ProvinsiController::class, 'update'])->middleware('permission:provinsi.update')->name('update');
        Route::delete('{provinsi}', [ProvinsiController::class, 'destroy'])->middleware('permission:provinsi.delete')->name('destroy');
    });

    Route::prefix('kota')->name('kota.')->group(function (): void {
        Route::get('by-provinsi/{provinsi}', [KotaController::class, 'byProvinsi'])->name('by-provinsi');
        Route::get('', [KotaController::class, 'index'])->middleware('permission:kota.view')->name('index');
        Route::post('', [KotaController::class, 'store'])->middleware('permission:kota.create')->name('store');
        Route::put('{kota}', [KotaController::class, 'update'])->middleware('permission:kota.update')->name('update');
        Route::delete('{kota}', [KotaController::class, 'destroy'])->middleware('permission:kota.delete')->name('destroy');
    });

    Route::prefix('kecamatan')->name('kecamatan.')->group(function (): void {
        Route::get('by-kota/{kota}', [KecamatanController::class, 'byKota'])->name('by-kota');
        Route::get('', [KecamatanController::class, 'index'])->middleware('permission:kecamatan.view')->name('index');
        Route::post('', [KecamatanController::class, 'store'])->middleware('permission:kecamatan.create')->name('store');
        Route::put('{kecamatan}', [KecamatanController::class, 'update'])->middleware('permission:kecamatan.update')->name('update');
        Route::delete('{kecamatan}', [KecamatanController::class, 'destroy'])->middleware('permission:kecamatan.delete')->name('destroy');
    });

    Route::prefix('kelurahan')->name('kelurahan.')->group(function (): void {
        Route::get('by-kecamatan/{kecamatan}', [KelurahanController::class, 'byKecamatan'])->name('by-kecamatan');
        Route::get('', [KelurahanController::class, 'index'])->middleware('permission:kelurahan.view')->name('index');
        Route::post('', [KelurahanController::class, 'store'])->middleware('permission:kelurahan.create')->name('store');
        Route::put('{kelurahan}', [KelurahanController::class, 'update'])->middleware('permission:kelurahan.update')->name('update');
        Route::delete('{kelurahan}', [KelurahanController::class, 'destroy'])->middleware('permission:kelurahan.delete')->name('destroy');
    });

    Route::prefix('ports')->name('ports.')->group(function (): void {
        Route::get('', [PortController::class, 'index'])->middleware('permission:ports.view')->name('index');
        Route::post('', [PortController::class, 'store'])->middleware('permission:ports.create')->name('store');
        Route::put('{port}', [PortController::class, 'update'])->middleware('permission:ports.update')->name('update');
        Route::delete('{port}', [PortController::class, 'destroy'])->middleware('permission:ports.delete')->name('destroy');
    });

    Route::prefix('trade-codes')->name('trade-codes.')->group(function (): void {
        Route::get('', [TradeCodeController::class, 'index'])->middleware('permission:trade-codes.view')->name('index');
        Route::post('', [TradeCodeController::class, 'store'])->middleware('permission:trade-codes.create')->name('store');
        Route::put('{tradeCode}', [TradeCodeController::class, 'update'])->middleware('permission:trade-codes.update')->name('update');
        Route::delete('{tradeCode}', [TradeCodeController::class, 'destroy'])->middleware('permission:trade-codes.delete')->name('destroy');
    });

    Route::prefix('komoditas')->name('komoditas.')->group(function (): void {
        Route::get('', [KomoditasController::class, 'index'])->middleware('permission:komoditas.view')->name('index');
        Route::post('', [KomoditasController::class, 'store'])->middleware('permission:komoditas.create')->name('store');
        Route::put('{komoditas}', [KomoditasController::class, 'update'])->middleware('permission:komoditas.update')->name('update');
        Route::delete('{komoditas}', [KomoditasController::class, 'destroy'])->middleware('permission:komoditas.delete')->name('destroy');
    });

    Route::prefix('source-statuses')->name('source-statuses.')->group(function (): void {
        Route::get('', [SourceStatusController::class, 'index'])->middleware('permission:source-statuses.view')->name('index');
        Route::post('', [SourceStatusController::class, 'store'])->middleware('permission:source-statuses.create')->name('store');
        Route::put('{sourceStatus}', [SourceStatusController::class, 'update'])->middleware('permission:source-statuses.update')->name('update');
        Route::delete('{sourceStatus}', [SourceStatusController::class, 'destroy'])->middleware('permission:source-statuses.delete')->name('destroy');
    });

    Route::prefix('species')->name('species.')->group(function (): void {
        Route::get('', [SpeciesController::class, 'index'])->middleware('permission:species.view')->name('index');
        Route::post('', [SpeciesController::class, 'store'])->middleware('permission:species.create')->name('store');
        Route::put('{species}', [SpeciesController::class, 'update'])->middleware('permission:species.update')->name('update');
        Route::delete('{species}', [SpeciesController::class, 'destroy'])->middleware('permission:species.delete')->name('destroy');
    });

    Route::prefix('entitas')->name('entitas.')->group(function (): void {
        Route::get('', [EntitasController::class, 'index'])->middleware('permission:entitas.view')->name('index');
        Route::post('', [EntitasController::class, 'store'])->middleware('permission:entitas.create')->name('store');
        Route::put('{entity}', [EntitasController::class, 'update'])->middleware('permission:entitas.update')->name('update');
        Route::delete('{entity}', [EntitasController::class, 'destroy'])->middleware('permission:entitas.delete')->name('destroy');
    });

});
