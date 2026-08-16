<?php

declare(strict_types=1);

use App\Http\Controllers\Customer\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('customer')->name('customer.')->group(function (): void {
    Route::get('', [CustomerController::class, 'index'])->middleware('permission:customer.view')->name('index');
    Route::post('', [CustomerController::class, 'store'])->middleware('permission:customer.edit')->name('store');
    Route::put('{customer}', [CustomerController::class, 'update'])->middleware('permission:customer.edit')->name('update');
    Route::delete('{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customer.delete')->name('destroy');
});
