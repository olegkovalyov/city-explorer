<?php

namespace App\Services;

use App\Contracts\Services\GeocodingServiceInterface;
use App\Contracts\Services\WeatherServiceInterface;
use App\Data\GetWeatherData;
use App\Enums\ErrorCode;
use App\Support\Result;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenWeatherMapWeatherService implements WeatherServiceInterface
{
    public function __construct(
        private readonly GeocodingServiceInterface $geocodingService
    ) {}

    public function getCurrentWeather(GetWeatherData $data): Result
    {
        $apiKey = config('openweathermap.api_key');
        $baseUrl = config('openweathermap.weather.base_url');
        $cacheTtl = config('openweathermap.weather.cache_ttl');

        if (empty($apiKey)) {
            Log::critical('OpenWeatherMap API key for Weather is not configured.');
            return Result::failure(ErrorCode::OPENWEATHERMAP_API_KEY_MISSING);
        }

        $latitude = $data->latitude;
        $longitude = $data->longitude;

        if (!$data->hasCoordinates() && $data->hasCity()) {
            Log::debug('WeatherService: Coordinates not provided, attempting geocoding.', ['city' => $data->city]);
            $geoResult = $this->geocodingService->getCoordinatesByCityName($data->city);

            if ($geoResult->isFailure()) {
                Log::warning('WeatherService: Geocoding failed.', ['city' => $data->city, 'error_code' => $geoResult->getErrorCode()?->value]);
                return $geoResult;
            }

            $coordinates = $geoResult->getValue();
            $latitude = $coordinates['latitude'];
            $longitude = $coordinates['longitude'];
            Log::debug('WeatherService: Geocoding successful.', ['city' => $data->city, 'lat' => $latitude, 'lon' => $longitude]);
        }

        if ($latitude === null || $longitude === null) {
            Log::error('WeatherService: Could not determine valid coordinates.', ['input_data' => $data]);
            return Result::failure(ErrorCode::WEATHER_INVALID_COORDINATES, 'Could not determine valid coordinates for weather lookup.');
        }

        $cacheKey = sprintf('weather_lat_%.4f_lon_%.4f', $latitude, $longitude);
        $query = [
            'lat' => $latitude,
            'lon' => $longitude,
            'appid' => $apiKey,
            'units' => 'metric',
        ];

        Log::debug('WeatherService: Attempting cache lookup.', ['cache_key' => $cacheKey, 'coords' => compact('latitude', 'longitude')]);

        $result = Cache::remember($cacheKey, $cacheTtl, function () use ($query, $latitude, $longitude, $baseUrl, $apiKey) {
            Log::debug('WeatherService: Cache miss. Calling Weather API.', ['coords' => compact('latitude', 'longitude')]);
            try {
                $response = Http::timeout(10)
                                ->get($baseUrl, $query);

                if (!$response->successful()) {
                    $apiResult = $this->handleApiError($response, 'getCurrentWeather', $query);
                    Log::debug('WeatherService: API returned error. Caching failure.', ['coords' => compact('latitude', 'longitude'), 'result' => $apiResult]);
                    return $apiResult;
                }

                $weatherData = $response->json();
                $formattedData = $this->formatWeatherData($weatherData);

                if ($formattedData === null) {
                    Log::error('WeatherService: Failed to format weather data.', ['response_body' => $weatherData]);
                    return Result::failure(ErrorCode::WEATHER_API_ERROR, 'Received invalid weather data format from API.');
                }

                $successResult = Result::success($formattedData);
                Log::debug('WeatherService: API call successful. Caching success.', ['coords' => compact('latitude', 'longitude'), 'result' => $successResult]);
                return $successResult;

            } catch (ConnectionException $e) {
                $context = ['error' => $e->getMessage()];
                Log::error('WeatherService: ConnectionException caught.', ['query' => $query, 'exception' => $e]);
                return Result::failure(ErrorCode::WEATHER_CONNECTION_ERROR, 'Failed to connect to OpenWeatherMap Weather API.', $context);
            } catch (Throwable $e) {
                $context = ['error' => $e->getMessage()];
                Log::error('WeatherService request failed unexpectedly.', ['query' => $query, 'exception' => $e]);
                return Result::failure(ErrorCode::UNEXPECTED_ERROR, 'An unexpected error occurred while communicating with OpenWeatherMap Weather API.', $context);
            }
        });

        Log::debug('WeatherService: Cache operation finished.', ['cache_key' => $cacheKey, 'final_result_is_success' => $result->isSuccess()]);

        return $result;
    }

    protected function handleApiError(Response $response, string $methodContext, array $requestData): Result
    {
        $status = $response->status();
        $body = $response->json() ?? $response->body();
        $logContext = [
            'status' => $status,
            'response_body' => $body,
            'request_data' => $requestData,
        ];
        Log::error("OpenWeatherMapWeatherService@{$methodContext} API request failed.", $logContext);

        $errorMessage = 'Failed to fetch weather data.';
        if (is_array($body) && isset($body['message'])) {
            $errorMessage = $body['message'];
        }

        $errorCode = ($status >= 500) ? ErrorCode::WEATHER_API_UNAVAILABLE : ErrorCode::WEATHER_API_ERROR;
        $context = ['api_status' => $status, 'api_response' => $body];

        return Result::failure($errorCode, $errorMessage, $context);
    }

    protected function formatWeatherData(?array $data): ?array
    {
        if (empty($data) || !isset($data['main'], $data['weather'][0])) {
            return null;
        }

        return [
            'temperature' => $data['main']['temp'] ?? null,
            'feels_like' => $data['main']['feels_like'] ?? null,
            'temp_min' => $data['main']['temp_min'] ?? null,
            'temp_max' => $data['main']['temp_max'] ?? null,
            'pressure' => $data['main']['pressure'] ?? null,
            'humidity' => $data['main']['humidity'] ?? null,
            'description' => $data['weather'][0]['description'] ?? 'N/A',
            'main_condition' => $data['weather'][0]['main'] ?? null,
            'icon_code' => $data['weather'][0]['icon'] ?? null,
            'icon_url' => isset($data['weather'][0]['icon'])
                            ? 'https://openweathermap.org/img/wn/' . $data['weather'][0]['icon'] . '@2x.png'
                            : null,
            'visibility' => $data['visibility'] ?? null,
            'wind_speed' => $data['wind']['speed'] ?? null,
            'wind_deg' => $data['wind']['deg'] ?? null,
            'wind_gust' => $data['wind']['gust'] ?? null,
            'clouds_percent' => $data['clouds']['all'] ?? null,
            'rain_1h' => $data['rain']['1h'] ?? null,
            'snow_1h' => $data['snow']['1h'] ?? null,
            'sunrise' => isset($data['sys']['sunrise']) ? date('c', $data['sys']['sunrise']) : null,
            'sunset' => isset($data['sys']['sunset']) ? date('c', $data['sys']['sunset']) : null,
            'timezone_offset_seconds' => $data['timezone'] ?? null,
            'city_name' => $data['name'] ?? null,
            'country_code' => $data['sys']['country'] ?? null,
        ];
    }
}
