<?php

namespace App\Services;

use App\Contracts\Services\GeocodingServiceContract;
use App\Enums\ErrorCode;
use App\Support\Result;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenWeatherMapGeocodingService implements GeocodingServiceContract
{
    protected ?string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openweathermap.key');
        $this->baseUrl = config('services.openweathermap.base_url', 'http://api.openweathermap.org/geo/1.0/direct');
    }

    public function getCoordinatesByCityName(string $cityName): Result
    {
        if (empty($this->apiKey)) {
            Log::critical('OpenWeatherMap API key is not configured.');
            return Result::failure(ErrorCode::OPENWEATHERMAP_API_KEY_MISSING);
        }

        try {
            $response = Http::timeout(10)
                ->get($this->baseUrl, [
                    'q' => $cityName,
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
    }

    private function handleApiError(Response $response, string $cityName): Result
    {
        Log::error('OpenWeatherMap Geocoding API request failed.', [
            'status' => $response->status(),
            'response' => $response->body(),
            'city' => $cityName,
        ]);

        return Result::failure(ErrorCode::OPENWEATHERMAP_API_ERROR, 'OpenWeatherMap API request failed.', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);
    }
}
