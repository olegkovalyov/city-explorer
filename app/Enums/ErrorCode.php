<?php

namespace App\Enums;

/**
 * Enum for representing specific error types returned by services.
 */
enum ErrorCode: int
{
    // General Errors
    case UNEXPECTED_ERROR = 1000;
    case DATABASE_ERROR = 2000;

    // Resource Errors
    case NOT_FOUND = 2001;
    case ALREADY_EXISTS = 2002;
    case BAD_REQUEST_ERROR = 2003;

    // API/Service Errors
    case EXTERNAL_SERVICE_ERROR = 3000;
    case API_KEY_MISSING = 3001;
    case FOURSQUARE_CONNECTION_ERROR = 3003; // Or another suitable code
    case FOURSQUARE_API_ERROR = 3100; // Specific range for Foursquare
    case FOURSQUARE_API_UNAVAILABLE = 3101;
    case FOURSQUARE_PLACE_NOT_FOUND = 7006; // Specific place ID not found

    // Weather API Errors (OpenWeatherMap)
    case WEATHER_API_KEY_MISSING = 8001;
    case WEATHER_CONNECTION_ERROR = 8002;
    case WEATHER_API_ERROR = 8003; // General/Client API error (4xx)
    case WEATHER_API_UNAVAILABLE = 8005; // Server error (5xx)
    case WEATHER_INVALID_COORDINATES = 8006; // Provided coordinates are invalid

    // OpenWeatherMap Errors (Starting from 3200)
    case OPENWEATHERMAP_API_KEY_MISSING = 3200;
    case OPENWEATHERMAP_CONNECTION_ERROR = 3201;
    case OPENWEATHERMAP_API_ERROR = 3202;
    case OPENWEATHERMAP_NO_RESULTS = 3203;

    // Favorite Place Errors
    case FAVORITE_PLACE_NOT_FOUND = 9001;

    // Profile Errors (Starting from 3300)
    case PROFILE_UPDATE_FAILED = 3300;
    case INVALID_CURRENT_PASSWORD = 3301; // Added for service layer check if needed, validation handles UI
    case PROFILE_DELETE_FAILED = 3302;

    /**
     * Get a default user-friendly message (optional).
     */
    public function message(): string
    {
        return match ($this) {
            self::NOT_FOUND => 'Resource not found.',
            self::DATABASE_ERROR => 'A database error occurred.',
            self::ALREADY_EXISTS => 'Resource already exists.',
            self::UNEXPECTED_ERROR => 'An unexpected error occurred.',
            // API Messages
            self::EXTERNAL_SERVICE_ERROR => 'An error occurred with an external service.',
            self::API_KEY_MISSING => 'Required API key is missing or not configured.',
            self::FOURSQUARE_API_ERROR => 'Could not retrieve data from Foursquare.', // Keep it generic for user
            self::FOURSQUARE_API_UNAVAILABLE => 'Foursquare service is temporarily unavailable.',
        };
    }
}
