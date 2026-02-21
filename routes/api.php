<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiAuthController::class , 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ApiAuthController::class , 'check']);
    Route::post('/logout', [ApiAuthController::class , 'logout']);
});

// Persistent Token Routes (for mobile session persistence)
Route::post('/token/create', [TokenController::class , 'create']);
Route::post('/token/validate', [TokenController::class , 'validate']);
Route::post('/token/refresh', [TokenController::class , 'refresh']);
Route::post('/token/revoke', [TokenController::class , 'revoke']);

// PIN Routes
Route::post('/pin/setup', [TokenController::class , 'setupPin']);
Route::post('/pin/verify', [TokenController::class , 'verifyPin']);
Route::post('/pin/disable', [TokenController::class , 'disablePin']);

Route::get('/riders', [RiderController::class , 'index']);
Route::post('/riders/{id}/location', [RiderController::class , 'updateLocationDemo']);
Route::post('/riders/{id}/order', [RiderController::class , 'orderRider']);
Route::post('/clients/{clientId}/respond', [RiderController::class , 'respondToClient']);
