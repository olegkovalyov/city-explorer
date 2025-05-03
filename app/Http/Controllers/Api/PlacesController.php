<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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

            return response()->json($places);

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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
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
