<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BillController;
use App\Http\Controllers\API\EntityController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ZoneController;
use App\Models\DenialReason;
use Illuminate\Support\Facades\Route;

// No need for /api prefix here as it's automatically added
Route::middleware('auth:sanctum')->group(function () {
   //  Route::get('/entities', [EntityController::class, 'index']);
   //  Route::post('/bills', [BillController::class, 'store']);
   //  Route::post('/payments', [PaymentController::class, 'store']);
   //  Route::get('/zones', [ZoneController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('denial-reasons', DenialReason::class);
});

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);