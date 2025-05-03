<?php

namespace App\Services;

use App\Contracts\Services\GeocodingServiceContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWeatherMapGeocodingService implements GeocodingServiceContract
{
    protected string $apiKey;
    protected string $baseUrl = 'http://api.openweathermap.org/geo/1.0/direct'; // Base URL for Geocoding API

    public function __construct()
    {
        $this->apiKey = config('services.openweathermap.key');
        if (empty($this->apiKey)) {
            throw new \InvalidArgumentException('OpenWeatherMap API key is not configured.');
        }
    }

    /**
     * Get coordinates by city name using OpenWeatherMap Geocoding API.
     *
     * @param string $cityName
     * @return array|null ['latitude' => float, 'longitude' => float] or null
     */
    public function getCoordinatesByCityName(string $cityName): ?array
    {
        try {
            $response = Http::timeout(10) // Add a timeout
                ->get($this->baseUrl, [
                    'q' => $cityName,
                    'limit' => 1, // We usually want the most relevant result
                    'appid' => $this->apiKey,
                ]);

            if (!$response->successful()) {
                Log::error('OpenWeatherMap Geocoding API request failed.', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'city' => $cityName,
                ]);
                return null;
            }

            $data = $response->json();

            // Check if the result is not empty and contains coordinates
            if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                return [
                    'latitude' => (float) $data[0]['lat'],
                    'longitude' => (float) $data[0]['lon'],
                ];
            } else {
                 Log::warning('OpenWeatherMap Geocoding API returned no results or invalid data.', [
                    'response' => $data,
                    'city' => $cityName,
                ]);
                return null; // City not found or data format incorrect
            }
        } catch (\Exception $e) {
            Log::error('Exception during OpenWeatherMap Geocoding API call.', [
                'message' => $e->getMessage(),
                'city' => $cityName,
                'trace' => $e->getTraceAsString(), // Optional: for detailed debugging
            ]);
            return null;
        }
    }
}
