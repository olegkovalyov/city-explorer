<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\WeatherServiceInterface;
use App\Data\GetWeatherData;
use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetWeatherRequest;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    public function __construct(private readonly WeatherServiceInterface $weatherService)
    {
    }

    public function index(GetWeatherRequest $request): JsonResponse
    {
        try {
            $weatherData = GetWeatherData::fromRequest($request);
            $result = $this->weatherService->getCurrentWeather($weatherData);
            return $this->handleServiceResult($result);
        } catch (\Exception $e) {
            Log::error('Unexpected error in WeatherController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleServiceResult(Result::failure(ErrorCode::UNEXPECTED_ERROR));
        }
    }

    private function handleServiceResult(Result $result): JsonResponse
    {
        if ($result->isSuccess()) {
            return response()->json($result->getValue());
        }

        $errorCode = $result->getErrorCode() ?? ErrorCode::UNEXPECTED_ERROR;
        $errorMessage = $result->getErrorMessage() ?? 'An unknown error occurred.';
        $context = $result->getErrorContext() ?? [];

        $logContext = array_merge($context, ['errorCode' => $errorCode->value]);
        Log::warning("Weather service call failed: {$errorMessage}", $logContext);

        $statusCode = match ($errorCode) {
            ErrorCode::BAD_REQUEST_ERROR,
            ErrorCode::WEATHER_INVALID_COORDINATES,
            ErrorCode::OPENWEATHERMAP_NO_RESULTS
            => 400,
            ErrorCode::WEATHER_API_KEY_MISSING,
            ErrorCode::OPENWEATHERMAP_API_KEY_MISSING
            => 503,
            ErrorCode::WEATHER_API_ERROR,
            ErrorCode::OPENWEATHERMAP_API_ERROR
            => 424,
            ErrorCode::WEATHER_CONNECTION_ERROR,
            ErrorCode::OPENWEATHERMAP_CONNECTION_ERROR,
            ErrorCode::WEATHER_API_UNAVAILABLE,
            => 504,
            default => 500,
        };

        return response()->json(['message' => $errorMessage, 'code' => $errorCode->value], $statusCode);
    }
}
