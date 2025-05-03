<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Services\PlacesService;

class PlacesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'limit' => ['sometimes', 'integer', 'min:1', 'max:50'], // Optional limit
            ]);

            $latitude = $validated['latitude'];
            $longitude = $validated['longitude'];
            $limit = 6; // Changed from previous value to 6

            $apiKey = config('services.foursquare.key') ?: env('FOURSQUARE_API_KEY');

            if (!$apiKey) {
                Log::error('Foursquare API key is not configured.');
                return response()->json(['message' => 'Foursquare API key not configured.'], 500);
            }

            $apiUrl = 'https://api.foursquare.com/v3/places/search';

            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Accept' => 'application/json',
            ])->get($apiUrl, [
                'll' => $latitude . ',' . $longitude,
                'limit' => $limit,
                'fields' => 'fsq_id,name,categories,location,photos', // Request needed fields
                // 'radius' => 1000, // Optional: search within 1km radius
            ]);

            if (!$response->successful()) {
                Log::error('Foursquare API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(
                    ['message' => 'Failed to fetch places from Foursquare.', 'details' => $response->json()],
                    $response->status() >= 500 ? 502 : $response->status() // Return 502 for server errors, otherwise Foursquare's error
                );
            }

            $results = $response->json()['results'] ?? [];

            // Simplify the results for the frontend
            $places = collect($results)->map(function ($place) {
                // Get primary category name and icon URL (if available)
                $categoryName = $place['categories'][0]['name'] ?? 'Place';
                $categoryIconUrl = null;
                if (!empty($place['categories'][0]['icon'])) {
                     $icon = $place['categories'][0]['icon'];
                     $categoryIconUrl = $icon['prefix'] . 'bg_64' . $icon['suffix']; // Combine prefix, size, suffix
                }

                // Get up to 5 photos
                $photosData = collect($place['photos'] ?? [])->take(5)->map(function ($photo) {
                    // Construct URL (e.g., 300x300 size)
                    return $photo['prefix'] . '300x300' . $photo['suffix'];
                })->all();

                return [
                    'id' => $place['fsq_id'] ?? null,
                    'name' => $place['name'] ?? 'Unknown Place',
                    'address' => $place['location']['formatted_address'] ?? ($place['location']['address'] ?? 'Address not available'),
                    'category' => $categoryName,
                    'category_icon' => $categoryIconUrl,
                    'photos' => $photosData, // Return array of photo URLs
                ];
            })->all();

            return response()->json(['places' => $places]);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid input.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Error fetching Foursquare places: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return response()->json(['message' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $fsq_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $fsq_id): JsonResponse
    {
        if (empty($fsq_id)) {
            return response()->json(['message' => 'Foursquare ID is required.'], 400);
        }

        $apiKey = config('services.foursquare.key') ?: env('FOURSQUARE_API_KEY');

        if (!$apiKey) {
            Log::error('Foursquare API key is not configured.');
            return response()->json(['message' => 'Foursquare API key not configured.'], 500);
        }

        // Use the Foursquare Places Details endpoint
        $apiUrl = "https://api.foursquare.com/v3/places/{$fsq_id}";

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Accept' => 'application/json',
            ])->get($apiUrl, [
                // Specify fields needed, especially 'photos'
                // 'location' field usually contains address details
                'fields' => 'fsq_id,name,categories,location,photos'
            ]);

            if (!$response->successful()) {
                Log::error('Foursquare API place details request failed.', [
                    'fsq_id' => $fsq_id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(
                    ['message' => 'Failed to fetch place details from Foursquare.', 'details' => $response->json()],
                    $response->status() >= 500 ? 502 : $response->status()
                );
            }

            $placeData = $response->json();

            // Format the result similarly to the index method
            $categoryName = $placeData['categories'][0]['name'] ?? 'Place';
            $categoryIconUrl = null;
            if (!empty($placeData['categories'][0]['icon'])) {
                $icon = $placeData['categories'][0]['icon'];
                $categoryIconUrl = $icon['prefix'] . 'bg_64' . $icon['suffix'];
            }

            // Format photos: use 'original' size for gallery
            $photosData = collect($placeData['photos'] ?? [])->map(function ($photo) {
                // Ensure prefix and suffix exist before concatenating
                if (!empty($photo['prefix']) && !empty($photo['suffix'])) {
                    return $photo['prefix'] . 'original' . $photo['suffix'];
                }
                return null; // Return null if data is incomplete
            })->filter()->values()->all(); // Filter out nulls and re-index

            $formattedPlace = [
                'id' => $placeData['fsq_id'] ?? $fsq_id, // Use fsq_id as primary ID
                'fsq_id' => $placeData['fsq_id'] ?? $fsq_id,
                'name' => $placeData['name'] ?? 'Unknown Place',
                'address' => $placeData['location']['formatted_address']
                             ?? $placeData['location']['address']
                             ?? ($placeData['address'] ?? 'Address not available'), // Check multiple possible fields
                'category' => $categoryName,
                'category_icon' => $categoryIconUrl,
                'photos' => $photosData, // Key part: array of photo URLs
                'location' => $placeData['location'] ?? null, // Include location object if needed
            ];

            return response()->json($formattedPlace);
        } catch (\Throwable $e) { // Catch any throwable error
            Log::error("Error fetching place details for {$fsq_id}: " . $e->getMessage(), [
                'fsq_id' => $fsq_id,
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to fetch place details.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
