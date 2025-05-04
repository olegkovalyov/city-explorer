<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\UserCityServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFavoriteCityRequest;
use App\Data\GetFavoriteCitiesData;
use App\Data\StoreFavoriteCityData;
use App\Data\DeleteFavoriteCityData;
use App\Support\Result;
use App\Enums\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FavoriteCityController extends Controller
{
    public function __construct(protected UserCityServiceInterface $cityService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $dto = new GetFavoriteCitiesData(user: $request->user());
        $result = $this->cityService->getFavoriteCities($dto);

        if ($result->isSuccess()) {
            return response()->json($result->getValue());
        }

        return $this->handleFailureResult($result, 'index');
    }

    public function store(StoreFavoriteCityRequest $request): JsonResponse
    {
        $dto = StoreFavoriteCityData::fromValidated($request->user(), $request->validated());
        $result = $this->cityService->storeFavoriteCity($dto);

        if ($result->isSuccess()) {
            $favoriteCity = $result->getValue(); // Get the FavoriteCity model
            $statusCode = $favoriteCity->wasRecentlyCreated ? 201 : 200; // Check if created or found
            return response()->json($favoriteCity, $statusCode);
        }

        return $this->handleFailureResult($result, 'store');
    }

    public function destroy(Request $request, $id): JsonResponse // Keep $id from route
    {
        $dto = new DeleteFavoriteCityData(user: $request->user(), cityId: (int) $id);
        $result = $this->cityService->deleteFavoriteCity($dto);

        if ($result->isSuccess()) {
            return response()->json(null, 204);
        }

        return $this->handleFailureResult($result, 'destroy', ['cityId' => $id]);
    }

    private function handleFailureResult(
        Result $result,
        string $method,
        array $extraLogContext = []
    ): JsonResponse {
        $errorCode = $result->getErrorCode() ?? ErrorCode::UNEXPECTED_ERROR;
        $errorMessage = $result->getErrorMessage() ?? ErrorCode::UNEXPECTED_ERROR->message();

        $logContext = array_merge($extraLogContext, [
            'error_code' => $errorCode->value,
            'message' => $errorMessage,
            'user_id' => auth()->id(),
        ]);
        Log::error("FavoriteCityController@{$method} failure", $logContext);

        $statusCode = match ($errorCode) {
            ErrorCode::NOT_FOUND => 404,
            ErrorCode::DATABASE_ERROR, ErrorCode::UNEXPECTED_ERROR => 500,
            default => 500,
        };

        return response()->json(['message' => $errorMessage], $statusCode);
    }
}
