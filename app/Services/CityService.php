<?php

namespace App\Services;

use App\Contracts\Services\UserCityServiceInterface;
use App\Data\DeleteFavoriteCityData;
use App\Data\GetFavoriteCitiesData;
use App\Data\StoreFavoriteCityData;
use App\Enums\ErrorCode;
use App\Models\FavoriteCity;
use App\Support\Result;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CityService implements UserCityServiceInterface
{

    public function getFavoriteCities(GetFavoriteCitiesData $data): Result
    {
        try {
            $favorites = $data->user->favoriteCities()
                ->orderBy('city_name') // Match controller logic
                ->get();
            return Result::success($favorites);
        } catch (QueryException $e) {
            Log::error('Database error fetching favorite cities in CityService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::DATABASE_ERROR);
        } catch (\Exception $e) {
            Log::error('Unexpected error fetching favorite cities in CityService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::UNEXPECTED_ERROR);
        }
    }

    public function storeFavoriteCity(StoreFavoriteCityData $data): Result
    {
        try {
            $favoriteCity = FavoriteCity::firstOrCreate(
                [
                    'user_id' => $data->user->id,
                    'city_name' => $data->cityName,
                ],
                [
                    'latitude' => $data->latitude,
                    'longitude' => $data->longitude,
                ]
            );
            return Result::success($favoriteCity);
        } catch (QueryException $e) {
            Log::error('Database error storing favorite city in CityService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'cityName' => $data->cityName,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::DATABASE_ERROR);
        } catch (\Exception $e) {
            Log::error('Unexpected error storing favorite city in CityService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'cityName' => $data->cityName,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::UNEXPECTED_ERROR);
        }
    }

    public function deleteFavoriteCity(DeleteFavoriteCityData $data): Result
    {
        try {
            $favoriteCity = FavoriteCity::where('user_id', $data->user->id)
                ->where('id', $data->cityId)
                ->first();

            if (!$favoriteCity) {
                return Result::failure(ErrorCode::NOT_FOUND);
            }

            if ($favoriteCity->delete()) {
                return Result::success(true);
            } else {
                Log::warning('FavoriteCity::delete returned false unexpectedly.', [
                    'userId' => $data->user->id,
                    'cityId' => $data->cityId,
                ]);
                return Result::failure(ErrorCode::UNEXPECTED_ERROR, 'Failed to delete favorite city record.');
            }
        } catch (QueryException $e) {
            Log::error('Database error deleting favorite city in CityService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'cityId' => $data->cityId,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::DATABASE_ERROR);
        } catch (\Exception $e) {
            Log::error('Unexpected error deleting favorite city in CityService: '.$e->getMessage(), [
                'userId' => $data->user->id,
                'cityId' => $data->cityId,
                'exception' => $e
            ]);
            return Result::failureFromException($e, ErrorCode::UNEXPECTED_ERROR);
        }
    }
}
