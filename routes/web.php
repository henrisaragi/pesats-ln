<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\RoutingController;
use Illuminate\Support\Facades\Route;

Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

require __DIR__.'/auth.php';
require __DIR__.'/onboarding.php';
require __DIR__.'/roles.php';
require __DIR__.'/permissions.php';
require __DIR__.'/master.php';
require __DIR__.'/users.php';
require __DIR__.'/komoditas-entitas.php';
require __DIR__.'/assesment.php';
require __DIR__.'/assesment-isi.php';
require __DIR__.'/panduan.php';
require __DIR__.'/kuota-perusahaan.php';
require __DIR__.'/kuota-tangkap.php';
require __DIR__.'/permohonan-export.php';
require __DIR__.'/documents.php';
require __DIR__.'/customer.php';
require __DIR__.'/settings.php';
require __DIR__.'/notifications.php';
require __DIR__.'/chat.php';

Route::group(['prefix' => '/'], function () {
    Route::get('', [RoutingController::class, 'index'])->middleware('auth')->name('root');
    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
    Route::get('{any}', [RoutingController::class, 'root'])->name('any');
});
