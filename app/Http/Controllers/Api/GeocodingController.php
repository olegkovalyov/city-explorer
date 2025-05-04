<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\GeocodingServiceContract;
use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeocodeCityRequest;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GeocodingController extends Controller
{
    protected GeocodingServiceContract $geocodingService;

    public function __construct(GeocodingServiceContract $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    public function getCoordinates(GeocodeCityRequest $request): JsonResponse
    {
        $cityName = $request->validated()['city'];
        $result = $this->geocodingService->getCoordinatesByCityName($cityName);

        if ($result->isSuccess()) {
            return response()->json($result->value);
        } else {
            return $this->handleGeocodingFailure($result, $cityName);
        }
    }

    private function handleGeocodingFailure(Result $result, string $cityName): JsonResponse
    {
        $errorCode = $result->getErrorCode();
        $errorContext = $result->errorContext;

        Log::warning('Geocoding failed in controller.', [
            'city' => $cityName,
            'error_code' => $errorCode->name ?? 'UNKNOWN',
            'context' => $errorContext,
        ]);

        switch ($errorCode) {
            case ErrorCode::OPENWEATHERMAP_NO_RESULTS:
                return response()->json(['message' => 'City not found.'], 404);

            case ErrorCode::OPENWEATHERMAP_API_KEY_MISSING:
            case ErrorCode::OPENWEATHERMAP_CONNECTION_ERROR:
            case ErrorCode::OPENWEATHERMAP_API_ERROR:
            case ErrorCode::UNEXPECTED_ERROR:
            default:
                return response()->json(['message' => 'An error occurred while fetching coordinates.'], 500);
        }
    }
}
