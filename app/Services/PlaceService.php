<?php

namespace App\Services;

use App\Data\GetFavoritePlacesData;
use App\Data\StoreFavoritePlaceData;
use App\Data\DeleteFavoritePlaceData;
use App\Models\FavoritePlace;
use App\Enums\ErrorCode;
use App\Support\Result;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class PlaceService
{
    public function getFavoritePlaces(GetFavoritePlacesData $data): Result
    {
        try {
            $favorites = $data->user->favoritePlaces()
                ->orderBy('created_at', 'desc')
                ->get();

            return Result::success($favorites);
        } catch (QueryException $e) {
            Log::error('Database error fetching favorite places in PlaceService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::DATABASE_ERROR);
        } catch (\Exception $e) {
            Log::error('Unexpected error fetching favorite places in PlaceService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::UNEXPECTED_ERROR);
        }
    }

    public function storeFavoritePlace(StoreFavoritePlaceData $data): Result
    {
        try {
            $attributesToFind = [
                'user_id' => $data->user->id,
                'fsq_id' => $data->fsqId,
            ];

            $attributesToCreate = [
                'user_id' => $data->user->id,
                'fsq_id' => $data->fsqId,
                'name' => $data->name,
                'address' => $data->address,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
                'photo_url' => $data->photoUrl,
                'category' => $data->category,
                'category_icon' => $data->categoryIcon,
            ];

            $favoritePlace = FavoritePlace::firstOrCreate($attributesToFind, $attributesToCreate);

            return Result::success($favoritePlace);
        } catch (QueryException $e) {
            Log::error('Database error storing favorite place in PlaceService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'fsqId' => $data->fsqId,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::DATABASE_ERROR);
        } catch (\Exception $e) {
            Log::error('Unexpected error storing favorite place in PlaceService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'fsqId' => $data->fsqId,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::UNEXPECTED_ERROR);
        }
    }

    public function deleteFavoritePlace(DeleteFavoritePlaceData $data): Result
    {
        try {
            $favoritePlace = FavoritePlace::where('user_id', $data->user->id)
                ->where('fsq_id', $data->fsqId)
                ->first();

            if (!$favoritePlace) {
                return Result::failure(ErrorCode::NOT_FOUND);
            }

            if ($favoritePlace->delete()) {
                return Result::success(true);
            } else {
                Log::warning('FavoritePlace::delete returned false unexpectedly.', [
                    'userId' => $data->user->id,
                    'fsqId' => $data->fsqId,
                ]);
                return Result::failure(ErrorCode::UNEXPECTED_ERROR, 'Failed to delete favorite place record.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting favorite place in PlaceService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'fsqId' => $data->fsqId,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::UNEXPECTED_ERROR);
        }
    }

}
