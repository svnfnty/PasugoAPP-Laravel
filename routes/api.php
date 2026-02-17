<?php

use App\Http\Controllers\Api\RiderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/riders', [RiderController::class , 'index']);
Route::post('/riders/{id}/location', [RiderController::class , 'updateLocationDemo']);
Route::post('/riders/{id}/order', [RiderController::class , 'orderRider']);
Route::post('/clients/{clientId}/respond', [RiderController::class , 'respondToClient']);
