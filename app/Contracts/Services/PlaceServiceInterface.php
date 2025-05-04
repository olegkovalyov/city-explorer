<?php

namespace App\Contracts\Services;

use App\Data\DeleteFavoritePlaceData;
use App\Data\GetFavoritePlacesData;
use App\Data\StoreFavoritePlaceData;
use App\Support\Result;

interface PlaceServiceInterface
{
    public function storeFavoritePlace(StoreFavoritePlaceData $data): Result;

    public function getFavoritePlaces(GetFavoritePlacesData $data): Result;

    public function deleteFavoritePlace(DeleteFavoritePlaceData $data): Result;
}
