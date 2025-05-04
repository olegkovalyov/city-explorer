<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenWeatherMap API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenWeatherMap API key.
    |
    */
    'api_key' => env('OPENWEATHERMAP_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Weather Service Configuration
    |--------------------------------------------------------------------------
    */
    'weather' => [
        'base_url' => env('OPENWEATHERMAP_WEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5/weather'),
        'cache_ttl' => env('OPENWEATHERMAP_WEATHER_CACHE_TTL', 900), // Default 15 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Geocoding Service Configuration
    |--------------------------------------------------------------------------
    */
    'geocoding' => [
        'base_url' => env('OPENWEATHERMAP_GEOCODING_BASE_URL', 'http://api.openweathermap.org/geo/1.0/direct'),
        'cache_ttl' => env('OPENWEATHERMAP_GEOCODING_CACHE_TTL', 86400), // Default 24 hours
    ],
];
