<?php

use App\Http\Controllers\Api\RideController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/rides', [RideController::class, 'store']);

    Route::post('/rides/{ride}/accept', [RideController::class, 'accept']);
    Route::post('/rides/{ride}/start', [RideController::class, 'start']);
    Route::post('/rides/{ride}/complete', [RideController::class, 'complete']);

    // for debugging
    Route::get('/rides/{ride}', [RideController::class, 'show']);
});
