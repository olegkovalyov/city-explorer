<?php

namespace App\Contracts\Services;

use App\Support\Result;

interface ExternalPlaceSearchInterface
{
    public function searchPlaces(float $latitude, float $longitude, int $radius, string $fields, int $limit = 10): Result;

    public function getPlaceDetails(string $fsqId): Result;
}
