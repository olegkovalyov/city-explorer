<?php

namespace App\Mappers;

use App\Data\PlaceData;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FoursquarePlaceMapper
{
    /**
     * Maps a raw Foursquare place array to a PlaceData DTO.
     *
     * @param array $place Raw place data from Foursquare API.
     * @return PlaceData
     */
    public static function fromFoursquare(array $place): PlaceData
    {
        $fsqId = Arr::get($place, 'fsq_id');
        $location = Arr::get($place, 'location', []);

        // Extract category info
        $categoryName = Arr::get($place, 'categories.0.name', 'Place'); // Default category name
        $categoryIconUrl = null;
        $iconData = Arr::get($place, 'categories.0.icon');
        if ($iconData && !empty($iconData['prefix']) && !empty($iconData['suffix'])) {
            // Construct icon URL (e.g., bg_64 size)
            $categoryIconUrl = $iconData['prefix'] . 'bg_64' . $iconData['suffix'];
        }

        // Extract and format photos (e.g., 'original' size for details, maybe different for list?)
        // Consider adding a size parameter if needed
        $photosData = collect(Arr::get($place, 'photos', []))
            ->map(function ($photo) {
                if (!empty($photo['prefix']) && !empty($photo['suffix'])) {
                    // Use 'original' size for photos in details view
                    return $photo['prefix'] . 'original' . $photo['suffix'];
                    // For list view, maybe use '300x300':
                    // return $photo['prefix'] . '300x300' . $photo['suffix'];
                }
                return null;
            })
            ->filter() // Remove nulls if prefix/suffix were missing
            ->values() // Re-index the array
            ->all();

        // Determine the best available address string
        $address = Arr::get($location, 'formatted_address',
                   Arr::get($location, 'address', 'Address not available') // Fallback
                );

        return new PlaceData(
            id: $fsqId ?? uniqid('place_'), // Ensure an ID exists, fallback if fsq_id missing
            fsqId: $fsqId,
            name: Arr::get($place, 'name', 'Unknown Place'),
            address: $address,
            category: $categoryName,
            categoryIcon: $categoryIconUrl,
            photos: $photosData,
            location: $location // Pass the whole location object
        );
    }

    /**
     * Maps a collection of raw Foursquare places to a collection of PlaceData DTOs.
     *
     * @param array $placesArray Array of raw place data from Foursquare API results.
     * @return Collection<int, PlaceData>
     */
    public static function fromFoursquareCollection(array $placesArray): Collection
    {
        return collect($placesArray)->map(fn ($place) => self::fromFoursquare($place));
    }
}
