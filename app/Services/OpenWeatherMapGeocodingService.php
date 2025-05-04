<?php

namespace App\Services;

use App\Contracts\Services\GeocodingServiceInterface;
use App\Enums\ErrorCode;
use App\Support\Result;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenWeatherMapGeocodingService implements GeocodingServiceInterface
{
    public function getCoordinatesByCityName(string $cityName): Result
    {
        $apiKey = config('openweathermap.api_key');
        $baseUrl = config('openweathermap.geocoding.base_url');
        $cacheTtl = config('openweathermap.geocoding.cache_ttl');

        if (empty($apiKey)) {
            Log::critical('OpenWeatherMap API key for Geocoding is not configured.');
            return Result::failure(ErrorCode::OPENWEATHERMAP_API_KEY_MISSING);
        }

        $normalizedCityName = strtolower(trim($cityName));
        $cacheKey = "openweathermap_geo_city_{$normalizedCityName}";

        Log::debug('OWM Geocoding: Attempting cache lookup.', ['cache_key' => $cacheKey, 'city' => $cityName]);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($cityName, $apiKey, $baseUrl) {
            try {
                $response = Http::timeout(10)
                    ->get($baseUrl, [
                        'q' => $cityName,
                        'limit' => 1,
                        'appid' => $apiKey,
                    ]);

                if (!$response->successful()) {
                    return $this->handleApiError($response, $cityName);
                }

                $data = $response->json();

                if (
                    !empty($data)
                    && isset($data[0]['lat'], $data[0]['lon'])
                ) {

                    return Result::success([
                        'latitude' => (float) $data[0]['lat'],
                        'longitude' => (float) $data[0]['lon'],
                    ]);
                } else {
                    Log::warning('OpenWeatherMap Geocoding API returned no results or invalid data.', [
                        'response' => $data,
                        'city' => $cityName,
                    ]);
                    return Result::failure(ErrorCode::OPENWEATHERMAP_NO_RESULTS);
                }
            } catch (ConnectionException $e) {
                Log::error('ConnectionException during OpenWeatherMap Geocoding API call.', [
                    'message' => $e->getMessage(),
                    'city' => $cityName,
                    'exception' => $e
                ]);
                return Result::failureFromException($e, ErrorCode::OPENWEATHERMAP_CONNECTION_ERROR);
            } catch (Throwable $e) {
                Log::error('Exception during OpenWeatherMap Geocoding API call.', [
                    'message' => $e->getMessage(),
                    'city' => $cityName,
                    'exception' => $e
                ]);
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

        if ($response->serverError()) {
            return Result::failure(ErrorCode::OPENWEATHERMAP_API_ERROR, 'OpenWeatherMap API server error.', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
        }
        return Result::failure(ErrorCode::OPENWEATHERMAP_API_ERROR, 'OpenWeatherMap API client error.', [
             'status' => $response->status(),
             'response' => $response->body()
        ]);
    }
}
