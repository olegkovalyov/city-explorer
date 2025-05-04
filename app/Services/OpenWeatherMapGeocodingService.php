<?php

namespace App\Services;

use App\Enums\ErrorCode;
use App\Support\Result;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenWeatherMapGeocodingService
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected int $cacheTtl = 3600; // 1 hour in seconds

    public function __construct()
    {
        $this->apiKey = config('services.openweathermap.key');
        $this->baseUrl = config('services.openweathermap.base_url', 'http://api.openweathermap.org/geo/1.0/direct');
    }

    public function getCoordinatesByCityName(string $cityName): Result
    {
        // Check API key before anything else
        if (empty($this->apiKey)) {
            Log::critical('OpenWeatherMap API key is not configured.');
            return Result::failure(ErrorCode::OPENWEATHERMAP_API_KEY_MISSING);
        }

        // Normalize city name for cache key consistency
        $normalizedCityName = strtolower(trim($cityName));
        $cacheKey = "openweathermap_geo_city_{$normalizedCityName}";

        // Log before cache attempt
        Log::debug('OWM Geocoding: Attempting cache lookup.', ['cache_key' => $cacheKey, 'city' => $cityName]);


        // Use Cache::remember to get from cache or execute the callback
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($cityName) {
            try {
                $response = Http::timeout(10)
                    ->get($this->baseUrl, [
                        'q' => $cityName, // Use original cityName for the API call
                        'limit' => 1,
                        'appid' => $this->apiKey,
                    ]);

                if (!$response->successful()) {
                    return $this->handleApiError($response, $cityName);
                }

                $data = $response->json();

                if (
                    !empty($data)
                    && isset($data[0]['lat'], $data[0]['lon'])
                ) {

                    // Cache successful results
                    return Result::success([
                        'latitude' => (float) $data[0]['lat'],
                        'longitude' => (float) $data[0]['lon'],
                    ]);
                } else {
                    Log::warning('OpenWeatherMap Geocoding API returned no results or invalid data.', [
                        'response' => $data,
                        'city' => $cityName,
                    ]);
                    // Cache failure results (e.g., city not found) to avoid repeated failed lookups
                    return Result::failure(ErrorCode::OPENWEATHERMAP_NO_RESULTS);
                }
            } catch (ConnectionException $e) {
                Log::error('ConnectionException during OpenWeatherMap Geocoding API call.', [
                    'message' => $e->getMessage(),
                    'city' => $cityName,
                    'exception' => $e
                ]);
                // Do not cache connection errors, as they might be temporary
                return Result::failureFromException($e, ErrorCode::OPENWEATHERMAP_CONNECTION_ERROR);
            } catch (Throwable $e) {
                Log::error('Exception during OpenWeatherMap Geocoding API call.', [
                    'message' => $e->getMessage(),
                    'city' => $cityName,
                    'exception' => $e
                ]);
                // Do not cache unexpected errors
                return Result::failureFromException($e, ErrorCode::UNEXPECTED_ERROR);
            }
        });
    }

    private function handleApiError(Response $response, string $cityName): Result
    {
        Log::error('OpenWeatherMap Geocoding API request failed.', [
            'status' => $response->status(),
            'response' => $response->body(),
            'city' => $cityName,
        ]);

        // Cache API error results (like 4xx errors) to avoid repeated failed lookups
        // Do not cache 5xx errors as they might be temporary server issues
        if ($response->serverError()) { // Status 500-599
            return Result::failure(ErrorCode::OPENWEATHERMAP_API_ERROR, 'OpenWeatherMap API server error.', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
        }
        // Cache client errors (4xx)
        return Result::failure(ErrorCode::OPENWEATHERMAP_API_ERROR, 'OpenWeatherMap API client error.', [
             'status' => $response->status(),
             'response' => $response->body()
        ]);
    }
}
