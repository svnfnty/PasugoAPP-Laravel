<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\RiderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiAuthController::class , 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ApiAuthController::class , 'check']);
    Route::post('/logout', [ApiAuthController::class , 'logout']);
});

Route::get('/riders', [RiderController::class , 'index']);
Route::post('/riders/{id}/location', [RiderController::class , 'updateLocationDemo']);
Route::post('/riders/{id}/order', [RiderController::class , 'orderRider']);
Route::post('/clients/{clientId}/respond', [RiderController::class , 'respondToClient']);
