<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreFavoritePlaceRequest;
use App\Services\PlaceService;
use App\Data\GetFavoritePlacesData;
use App\Data\StoreFavoritePlaceData;
use App\Data\DeleteFavoritePlaceData;
use App\Enums\ErrorCode;
use Illuminate\Http\JsonResponse;

class FavoritePlaceController extends Controller
{
    public function __construct(protected PlaceService $placeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $dto = new GetFavoritePlacesData(user: $request->user());
        $result = $this->placeService->getFavoritePlaces($dto);

        if ($result->isSuccess()) {
            return response()->json($result->getValue());
        }

        return $this->handleFailureResult($result, 'index');
    }

    public function store(StoreFavoritePlaceRequest $request): JsonResponse
    {
        $dto = StoreFavoritePlaceData::fromValidated($request->user(), $request->validated());
        $result = $this->placeService->storeFavoritePlace($dto);

        if ($result->isSuccess()) {
            $favoritePlace = $result->getValue();
            $statusCode = $favoritePlace->wasRecentlyCreated ? 201 : 200;
            return response()->json($favoritePlace, $statusCode);
        }

        return $this->handleFailureResult($result, 'store');
    }

    public function destroy(Request $request, string $fsqId): JsonResponse
    {
        $dto = new DeleteFavoritePlaceData(user: $request->user(), fsqId: $fsqId);
        $result = $this->placeService->deleteFavoritePlace($dto);

        if ($result->isSuccess()) {
            return response()->json(null, 204);
        }

        return $this->handleFailureResult($result, 'destroy', ['fsqId' => $fsqId]);
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
        Log::error("FavoritePlaceController@{$method} failure", $logContext);

        $statusCode = match ($errorCode) {
            ErrorCode::NOT_FOUND => 404,
            ErrorCode::DATABASE_ERROR, ErrorCode::UNEXPECTED_ERROR => 500,
            default => 500,
        };

        return response()->json(['message' => $errorMessage], $statusCode);
    }
}
