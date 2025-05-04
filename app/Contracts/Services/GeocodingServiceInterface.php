<?php

namespace App\Contracts\Services;

use App\Support\Result;

interface GeocodingServiceInterface
{
    public function getCoordinatesByCityName(string $cityName): Result;
}
