<?php

use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrsController;

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

    Route::post('/rides/{ride}/review', [ReviewController::class, 'store']);
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::get('/drivers/{driver}/reviews', [ReviewController::class, 'driverReviews']);
    Route::get('/drivers/{driver}/rating', [ReviewController::class, 'rating']);
    
    Route::get('/rides/mine', [RideController::class, 'mine']);
    Route::get('/rides/driver', [RideController::class, 'driverRides']);
    Route::get('/rides/available', [RideController::class, 'available']); 
    
    Route::patch('/me', [AuthController::class, 'update']);

    // for debugging
    Route::get('/rides/{ride}', [RideController::class, 'show']);
});

Route::get('/geocode/autocomplete', [OrsController::class, 'autocomplete']);
