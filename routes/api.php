<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FavoriteCityController;
use App\Http\Controllers\Api\GeocodingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Geocoding route
Route::get('/geocode', [GeocodingController::class, 'getCoordinates']);

// Routes for Favorite Cities API
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('favorite-cities', FavoriteCityController::class)->except(['update', 'show']);
});
