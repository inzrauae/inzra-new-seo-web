<?php

use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\Api\PaypalOrderController;
use App\Http\Controllers\OrderDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Inzra order backend',
        'status' => 'ok',
        'createOrderEndpoint' => url('/api/paypal/orders/create'),
        'captureOrderEndpoint' => url('/api/paypal/orders/capture'),
        'ordersDashboard' => url('/admin/orders'),
    ]);
});

Route::prefix('api/paypal/orders')->group(function (): void {
    Route::options('/create', [PaypalOrderController::class, 'options']);
    Route::post('/create', [PaypalOrderController::class, 'create']);
    Route::options('/capture', [PaypalOrderController::class, 'options']);
    Route::post('/capture', [PaypalOrderController::class, 'capture']);
});

Route::prefix('admin')->group(function (): void {
    Route::get('/login', [AdminLoginController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin.session')->group(function (): void {
        Route::get('/orders', [OrderDashboardController::class, 'index'])->name('admin.orders');
    });
});
