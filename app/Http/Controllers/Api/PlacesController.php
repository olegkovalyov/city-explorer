<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\ExternalPlaceSearchInterface;
use App\Contracts\Services\PlaceServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Support\Result;
use App\Enums\ErrorCode;
use App\Mappers\FoursquarePlaceMapper;
use App\Http\Requests\SearchPlacesRequest;

class PlacesController extends Controller
{
    public function __construct(
        protected ExternalPlaceSearchInterface $foursquareService,
        protected PlaceServiceInterface $placeService
    ) {
    }

    public function index(SearchPlacesRequest $request): JsonResponse
    {
        $validatedData = $request->validatedWithDefaults();

        $result = $this->foursquareService->searchPlaces(
            latitude: $validatedData['latitude'],
            longitude: $validatedData['longitude']
        );

        if ($result->isFailure()) {
            return $this->handleFoursquareFailure($result, 'index');
        }

        $foursquareResults = $result->getValue();
        $places = FoursquarePlaceMapper::fromFoursquareCollection($foursquareResults);

        return response()->json(['places' => $places]);
    }

    public function show(string $fsq_id): JsonResponse
    {
        if (empty($fsq_id)) {
            return response()->json(['message' => 'Foursquare ID is required.'], 400);
        }

        $result = $this->foursquareService->getPlaceDetails($fsq_id);
        if ($result->isFailure()) {
            return $this->handleFoursquareFailure($result, 'show', ['fsq_id' => $fsq_id]);
        }

        $placeData = $result->getValue();
        $formattedPlace = FoursquarePlaceMapper::fromFoursquare($placeData);

        return response()->json($formattedPlace);
    }

    private function handleFoursquareFailure(Result $result, string $method, array $extraLogContext = []): JsonResponse
    {
        $errorCode = $result->getErrorCode() ?? ErrorCode::UNEXPECTED_ERROR;
        $errorMessage = $result->getErrorMessage() ?? ErrorCode::UNEXPECTED_ERROR->message();
        $apiDetails = $result->getErrorContext();

        $logContext = array_merge($extraLogContext, [
            'error_code' => $errorCode->value,
            'message' => $errorMessage,
            'user_id' => auth()->id(),
            'api_details' => $apiDetails
        ]);
        Log::error("PlacesController@{$method} failure calling Foursquare", $logContext);

        $statusCode = match ($errorCode) {
            ErrorCode::API_KEY_MISSING => 500,
            ErrorCode::FOURSQUARE_API_UNAVAILABLE => 503,
            ErrorCode::FOURSQUARE_API_ERROR => 502,
            default => 500,
        };

        $responseData = ['message' => $errorMessage];

        return response()->json($responseData, $statusCode);
    }
}
