<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PCController;
use App\Http\Controllers\Api\RentalSessionController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AnalyticsController;

// Public routes
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('api.login');
Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum')->name('api.logout');

// Public read-only routes (untuk monitoring tanpa login)
Route::get('/pcs', [PCController::class, 'index'])->name('api.pcs.index');
Route::get('/pcs/{pc}', [PCController::class, 'show'])->name('api.pcs.show');

Route::get('/menu', [MenuController::class, 'index'])->name('api.menu.index.public');
Route::get('/menu/{menu}', [MenuController::class, 'show'])->name('api.menu.show.public');

Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index.public');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('api.orders.show.public');

Route::get('/analytics/revenue', [AnalyticsController::class, 'revenue'])->name('api.analytics.revenue');
Route::get('/analytics/pc-usage', [AnalyticsController::class, 'pcUsage'])->name('api.analytics.pcUsage');
Route::get('/analytics/fnb', [AnalyticsController::class, 'fAndB'])->name('api.analytics.fnb');

// Public write operations (untuk sistem internal warnet tanpa strict authentication)
// Sessions
Route::get('/sessions', [RentalSessionController::class, 'index'])->name('api.sessions.index.public');
Route::post('/sessions', [RentalSessionController::class, 'store'])->name('api.sessions.store.public');
Route::patch('/sessions/{session}/extend', [RentalSessionController::class, 'extend'])->name('api.sessions.extend.public');
Route::post('/sessions/{session}/pause', [RentalSessionController::class, 'pause'])->name('api.sessions.pause.public');
Route::post('/sessions/{session}/resume', [RentalSessionController::class, 'resume'])->name('api.sessions.resume.public');
Route::post('/sessions/{session}/complete', [RentalSessionController::class, 'complete'])->name('api.sessions.complete.public');

// Menu (public untuk internal management)
Route::post('/menu', [MenuController::class, 'store'])->name('api.menu.store.public');
Route::put('/menu/{menu}', [MenuController::class, 'update'])->name('api.menu.update.public');
Route::delete('/menu/{menu}', [MenuController::class, 'destroy'])->name('api.menu.destroy.public');

// Orders
Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('api.orders.updateStatus');
Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('api.orders.updateStatus.put');

// Payments
Route::post('/payments', [PaymentController::class, 'store'])->name('api.payments.store');
Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('api.payments.confirm');
Route::get('/payments/{payment}/check-status', [PaymentController::class, 'checkStatus'])->name('api.payments.checkStatus');
Route::post('/payments/{payment}/simulate', [PaymentController::class, 'simulate'])->name('api.payments.simulate');
Route::post('/payments/webhook/qris', [PaymentController::class, 'webhookQris'])->name('api.payments.webhook');

// Protected routes (require Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    
    // PC Management
    Route::apiResource('pc', PCController::class);
    Route::post('/pc/{pc}/control', [PCController::class, 'control'])->name('pc.control');
    Route::get('/pc/{pc}/history', [PCController::class, 'history'])->name('pc.history');

    // Rental Sessions (show, update, destroy)
    Route::get('/sessions/{session}', [RentalSessionController::class, 'show'])->name('api.sessions.show');
    Route::put('/sessions/{session}', [RentalSessionController::class, 'update'])->name('api.sessions.update');
    Route::delete('/sessions/{session}', [RentalSessionController::class, 'destroy'])->name('api.sessions.destroy');

    // Analytics
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
