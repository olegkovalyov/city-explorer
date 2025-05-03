<?php

namespace App\Contracts\Services;

/**
 * Interface GeocodingServiceContract
 * Defines the contract for services that provide geocoding functionality.
 */
interface GeocodingServiceContract
{
    /**
     * Get coordinates (latitude and longitude) for a given city name.
     *
     * @param string $cityName The name of the city.
     * @return array|null An array containing 'latitude' and 'longitude', or null if not found.
     */
    public function getCoordinatesByCityName(string $cityName): ?array;
}
