<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RentalController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Fitur Penyewa
    Route::get('/garages/available', [RentalController::class, 'availableGarages']);
    Route::post('/rentals', [RentalController::class, 'store']);

    // Fitur Admin
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::post('/garages/batch', [AdminController::class, 'createGarages']);
        Route::patch('/tenants/{user}/verify', [AdminController::class, 'verifyTenant']);
        Route::get('/rentals/active-today', [AdminController::class, 'activeRentalsToday']);
        Route::post('/rentals/{rental}/extend', [AdminController::class, 'extendRental']);
        Route::get('/reports', [AdminController::class, 'report']);
    });
});
