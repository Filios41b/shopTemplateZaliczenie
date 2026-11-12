<?php

use App\Http\Controllers\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;

Route::post('/orders', [OrderController::class, 'store']);

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/sync', [CartController::class, 'sync']);
});
