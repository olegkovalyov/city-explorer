<?php

namespace App\Contracts\Services;

use App\Support\Result;

/**
 * Interface GeocodingServiceContract
 * Defines the contract for services that provide geocoding functionality.
 */
interface GeocodingServiceContract
{
    /**
     * Get coordinates by city name.
     *
     * @param string $cityName
     * @return Result Contains ['latitude' => float, 'longitude' => float] on success, or ErrorCode on failure.
     */
    public function getCoordinatesByCityName(string $cityName): Result;
}
