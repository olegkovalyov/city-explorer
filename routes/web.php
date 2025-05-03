<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Default dashboard route (can be kept or removed if not needed)
Route::get('/dashboard', function () {
    // Redirect to city-explorer or render a different dashboard if needed
    // For now, let's keep rendering the default Dashboard component
    return Inertia::render('Dashboard', [
        'mapboxToken' => env('MAPBOX_ACCESS_TOKEN')
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// City Explorer route
Route::get('/city-explorer', function () {
    return Inertia::render('CityExplorer');
})->middleware(['auth', 'verified'])->name('city-explorer'); // Added name 'city-explorer'

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
