<?php

use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\AdminUserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrsController;
use App\Http\Controllers\Api\PaymentPreferenceController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);

Route::middleware(['auth:sanctum', 'not_suspended'])->group(function () {
    Route::post('/rides', [RideController::class, 'store']);
    Route::post('/rides/{ride}/cancel', [RideController::class, 'cancel']);

    Route::post('/rides/{ride}/accept', [RideController::class, 'accept']);
    Route::post('/rides/{ride}/start', [RideController::class, 'start']);
    Route::post('/rides/{ride}/complete', [RideController::class, 'complete']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::delete('/me', [AuthController::class, 'destroy']);
    Route::get('/me', [AuthController::class, 'me']); // may scrape
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/sessions', [AuthController::class, 'sessions']);
    Route::post('/driver/apply', [AuthController::class, 'applyDriver']);

    Route::post('/payments/{payment}/pay', [PaymentController::class, 'pay']);
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirmCashPayment']);
    Route::post('/payments/{payment}/report-unpaid', [PaymentController::class, 'reportUnpaidCash']);
    Route::post('/payments/{payment}/stripe-checkout', [PaymentController::class, 'createStripeCheckout']);
    Route::post('/payments/{payment}/stripe-confirm', [PaymentController::class, 'confirmStripeCheckout']);

    Route::post('/rides/{ride}/review', [ReviewController::class, 'store']);
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::get('/drivers/{driver}/reviews', [ReviewController::class, 'driverReviews']);
    Route::get('/drivers/{driver}/rating', [ReviewController::class, 'rating']);
    
    Route::get('/rides/mine', [RideController::class, 'mine']);
    Route::get('/rides/driver', [RideController::class, 'driverRides']);
    Route::get('/rides/available', [RideController::class, 'available']); 
    
    Route::patch('/me', [AuthController::class, 'update']);
    Route::get('/driver/me', [DriverController::class, 'me']);
    Route::patch('/driver/me', [DriverController::class, 'updateMe']);
    Route::patch('/driver/location', [DriverController::class, 'updateLocation']);
    Route::get('/driver/{driver_id}/profile', [DriverController::class, 'profile']);

    Route::get('/driver/active-ride', [RideController::class, 'driverActive']);
    Route::get('/users/{id}/reviews', [ReviewController::class, 'forUser']);
    
    // for debugging
    Route::get('/rides/{ride}', [RideController::class, 'show']);

    Route::get('/me/preferred-payment', [PaymentPreferenceController::class, 'getPreferredPaymentMethod']);

    Route::patch('/me/preferred-payment', [PaymentPreferenceController::class, 'updatePreferredPaymentMethod']);

    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::patch('/admin/users/{user}/suspended', [AdminUserController::class, 'setSuspended']);
    Route::patch('/admin/users/{user}/role', [AdminUserController::class, 'setRole']);
});

Route::get('/geocode/autocomplete', [OrsController::class, 'autocomplete']);
Route::get('/directions', [OrsController::class, 'directions']);

