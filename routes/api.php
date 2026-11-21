<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\Api\TransactionsController;
use App\Http\Middleware\RequireAdmin;
use App\Http\Middleware\RequireApiToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->middleware('throttle:api')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
    Route::middleware(RequireApiToken::class)->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
        Route::get('/products/{id}', [ProductsController::class, 'show'])->whereNumber('id')->name('products.show');
        Route::post('/products/{slug}/purchase', [ProductsController::class, 'purchase'])->name('products.purchase');
        Route::get('/transactions', [TransactionsController::class, 'index'])->name('transactions.index');
        Route::middleware(RequireAdmin::class)->group(function (): void {
            Route::post('/products', [ProductsController::class, 'store'])->name('products.store');
            Route::match(['PUT', 'PATCH'], '/products/{id}', [ProductsController::class, 'update'])->whereNumber('id')->name('products.update');
            Route::delete('/products/{id}', [ProductsController::class, 'destroy'])->whereNumber('id')->name('products.destroy');
        });
    });
});
