<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Foursquare API Key
    |--------------------------------------------------------------------------
    |
    | Your Foursquare API key.
    |
    */
    'api_key' => env('FOURSQUARE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Foursquare API Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('FOURSQUARE_BASE_URL', 'https://api.foursquare.com/v3/places'),

    /*
    |--------------------------------------------------------------------------
    | Foursquare API Cache TTL
    |--------------------------------------------------------------------------
    |
    | Time-to-live for cached API responses in seconds.
    |
    */
    'cache_ttl' => env('FOURSQUARE_CACHE_TTL', 604800), // Default 1 week

    /*
    |--------------------------------------------------------------------------
    | Default API Parameters
    |--------------------------------------------------------------------------
    */
    'search_limit' => env('FOURSQUARE_DEFAULT_SEARCH_LIMIT', 6),
];
