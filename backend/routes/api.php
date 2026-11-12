
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/orders', [OrderController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/sync', [CartController::class, 'sync']);
});

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    });
