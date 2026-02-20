<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientAuthController;
use App\Http\Controllers\RiderAuthController;
// We'll create OrderController later, but let's define auth routes first

Route::get('/', function () {
    return view('welcome');
});

// Client Routes
Route::prefix('client')->name('client.')->group(function () {
    Route::get('/login', [ClientAuthController::class , 'showLoginForm'])->name('login');
    Route::post('/login', [ClientAuthController::class , 'login']);
    Route::get('/register', [ClientAuthController::class , 'showRegisterForm'])->name('register');
    Route::post('/register', [ClientAuthController::class , 'register']);

    Route::middleware('auth:client')->group(function () {
            Route::post('/logout', [ClientAuthController::class , 'logout'])->name('logout');
            Route::get('/dashboard', [ClientAuthController::class , 'dashboard'])->name('dashboard');

            Route::get('/order/create', [\App\Http\Controllers\OrderController::class , 'create'])->name('order.create');
            Route::post('/order', [\App\Http\Controllers\OrderController::class , 'store'])->name('order.store');
            Route::get('/riders/map', function () {
                    $client = Auth::guard('client')->user();
                    $activeMission = \App\Models\Order::where('client_id', $client->id)
                        ->whereIn('status', ['mission_accepted', 'accepted', 'picked_up'])
                        ->with('rider')
                        ->latest()
                        ->first();
                    return view('riders.map', compact('activeMission'));
                }
                )->name('riders.map');

                // Client Ordering Action
                Route::post('/riders/{id}/order', [\App\Http\Controllers\Api\RiderController::class , 'orderRider'])->name('order.rider');
                Route::post('/riders/{id}/cancel', [\App\Http\Controllers\Api\RiderController::class , 'cancelRequest'])->name('order.cancel');
                Route::post('/location', [\App\Http\Controllers\Api\RiderController::class , 'updateClientLocation'])->name('location.update');
            }
            );
        });

// Rider Routes
Route::prefix('rider')->name('rider.')->group(function () {
    Route::get('/login', [RiderAuthController::class , 'showLoginForm'])->name('login');
    Route::post('/login', [RiderAuthController::class , 'login']);
    Route::get('/register', [RiderAuthController::class , 'showRegisterForm'])->name('register');
    Route::post('/register', [RiderAuthController::class , 'register']);

    Route::middleware('auth:rider')->group(function () {
            Route::post('/logout', [RiderAuthController::class , 'logout'])->name('logout');
            Route::get('/dashboard', [RiderAuthController::class , 'dashboard'])->name('dashboard');
            Route::post('/location', [\App\Http\Controllers\Api\RiderController::class , 'updateLocation'])->name('location.update');

            Route::post('/order/{order}/accept', [\App\Http\Controllers\OrderController::class , 'accept'])->name('order.accept');
            Route::patch('/order/{order}/status', [\App\Http\Controllers\OrderController::class , 'updateStatus'])->name('order.update');

            // Rider Responding Action
            Route::post('/clients/{clientId}/respond', [\App\Http\Controllers\Api\RiderController::class , 'respondToClient'])->name('rider.respond');
            Route::post('/order/place-from-chat', [\App\Http\Controllers\Api\RiderController::class , 'placeOrderFromChat'])->name('order.place_from_chat');
        }
        );
    });

// Shared Real-time Routes
Route::post('/chat/send', [\App\Http\Controllers\Api\RiderController::class , 'sendMessage'])->name('chat.send');
Route::get('/chat/history', [\App\Http\Controllers\Api\RiderController::class , 'getChatHistory'])->name('chat.history');
