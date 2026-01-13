<?php

use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/rides', [RideController::class, 'store']);

    Route::post('/rides/{ride}/accept', [RideController::class, 'accept']);
    Route::post('/rides/{ride}/start', [RideController::class, 'start']);
    Route::post('/rides/{ride}/complete', [RideController::class, 'complete']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::delete('/me', [AuthController::class, 'destroy']);
    Route::get('/me', [AuthController::class, 'me']); // may scrape

    Route::post('/payments/{payment}/pay', [PaymentController::class, 'pay']);
    Route::get('/payments', [PaymentController::class, 'index']);


    // for debugging
    Route::get('/rides/{ride}', [RideController::class, 'show']);
});
