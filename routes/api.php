<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FavoriteCityController;
use App\Http\Controllers\Api\PlacesController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\Api\FavoritePlaceController;

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

// Place Details Route (requires authentication)
Route::middleware('auth:sanctum')->get('/places/{fsq_id}', [PlacesController::class, 'show'])->where('fsq_id', '[a-zA-Z0-9]+');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // New Foursquare Places route
    Route::get('/places', [PlacesController::class, 'index']);

    // Weather
    Route::get('/weather', [WeatherController::class, 'index']);

    // Routes for Favorite Cities API
    Route::apiResource('favorite-cities', FavoriteCityController::class)->except(['update', 'show']);

    // Favorite Places
    Route::get('/favorite-places', [FavoritePlaceController::class, 'index'])->name('api.favorite-places.index');
    Route::post('/favorite-places', [FavoritePlaceController::class, 'store'])->name('api.favorite-places.store');
    Route::delete('/favorite-places/{fsq_id}', [FavoritePlaceController::class, 'destroy'])->name('api.favorite-places.destroy');
});
