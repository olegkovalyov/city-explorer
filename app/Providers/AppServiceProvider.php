<?php

namespace App\Providers;

use App\Contracts\Services\ExternalPlaceSearchInterface;
use App\Contracts\Services\GeocodingServiceInterface;
use App\Contracts\Services\PlaceServiceInterface;
use App\Contracts\Services\UserCityServiceInterface;
use App\Contracts\Services\UserProfileServiceInterface;
use App\Contracts\Services\WeatherServiceInterface;

use App\Services\CityService;
use App\Services\FoursquareService;
use App\Services\OpenWeatherMapGeocodingService;
use App\Services\OpenWeatherMapWeatherService;
use App\Services\PlaceService;
use App\Services\ProfileService;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UserCityServiceInterface::class, CityService::class);
        $this->app->singleton(ExternalPlaceSearchInterface::class, FoursquareService::class);
        $this->app->singleton(GeocodingServiceInterface::class, OpenWeatherMapGeocodingService::class);
        $this->app->singleton(WeatherServiceInterface::class, OpenWeatherMapWeatherService::class);
        $this->app->singleton(PlaceServiceInterface::class, PlaceService::class);
        $this->app->singleton(UserProfileServiceInterface::class, ProfileService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
