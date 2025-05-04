<?php

namespace App\Contracts\Services;

use App\Support\Result;

interface ExternalPlaceSearchInterface
{
    public function searchPlaces(float $latitude, float $longitude): Result;

    public function getPlaceDetails(string $fsqId): Result;
}
