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
require __DIR__.'/notifications.php';

Route::group(['prefix' => '/'], function () {
    Route::get('', [RoutingController::class, 'index'])->middleware('auth')->name('root');
    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
    Route::get('{any}', [RoutingController::class, 'root'])->name('any');
});
