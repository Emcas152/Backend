<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AppointmentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register-patient', [AuthController::class, 'registerPatient']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register-staff', [AuthController::class, 'registerStaff']); // Admin only

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Patients
    Route::apiResource('patients', PatientController::class);
    Route::post('/patients/{id}/upload-photo', [PatientController::class, 'uploadPhoto']);
    Route::post('/patients/{id}/upload-document', [PatientController::class, 'uploadDocument']);
    Route::post('/patients/{patient}/documents/{document}/sign', [PatientController::class, 'signDocument']);
    Route::post('/patients/{id}/loyalty/add', [PatientController::class, 'addLoyaltyPoints']);
    Route::post('/patients/{id}/loyalty/redeem', [PatientController::class, 'redeemLoyaltyPoints']);

    // Products
    Route::apiResource('products', ProductController::class);
    Route::post('/products/{id}/adjust-stock', [ProductController::class, 'adjustStock']);

    // Sales (TPV)
    Route::apiResource('sales', SaleController::class);
    Route::get('/sales-statistics', [SaleController::class, 'statistics']);

    // Appointments
    Route::apiResource('appointments', AppointmentController::class);
    Route::patch('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
});
