<?php

namespace App\Contracts\Services;

use App\Data\DeleteFavoriteCityData;
use App\Data\GetFavoriteCitiesData;
use App\Data\StoreFavoriteCityData;
use App\Support\Result;

interface UserCityServiceInterface
{
    public function getFavoriteCities(GetFavoriteCitiesData $data): Result;

    public function storeFavoriteCity(StoreFavoriteCityData $data): Result;

    public function deleteFavoriteCity(DeleteFavoriteCityData $data): Result;
}
