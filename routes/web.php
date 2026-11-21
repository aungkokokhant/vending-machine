<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductsController;
use App\Http\Middleware\RequireAdmin;
use App\Http\Middleware\RequireSessionUser;
use App\Routing\AttributeRouteRegistrar;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/products');
Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::middleware(RequireSessionUser::class)->group(function (): void {
    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
    Route::get('/products/{slug}/purchase', [ProductsController::class, 'purchaseForm'])->name('products.purchase.form');
    AttributeRouteRegistrar::register(ProductsController::class);
    Route::get('/transactions', [ProductsController::class, 'history'])->name('transactions.index');
    Route::middleware(RequireAdmin::class)->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/products/{id}/delete', [ProductsController::class, 'confirmDelete'])->whereNumber('id')->name('products.delete');
        Route::resource('products', ProductsController::class)->except('index')->parameters(['products' => 'id'])->whereNumber('id');
    });
});
