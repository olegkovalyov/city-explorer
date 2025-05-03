<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\GeocodingServiceContract;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class GeocodingController extends Controller
{
    protected GeocodingServiceContract $geocodingService;

    /**
     * Inject the geocoding service.
     *
     * @param GeocodingServiceContract $geocodingService
     */
    public function __construct(GeocodingServiceContract $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    /**
     * Get coordinates for a given city name.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCoordinates(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'city' => 'required|string|max:255',
            ]);

            $cityName = $validated['city'];

            $coordinates = $this->geocodingService->getCoordinatesByCityName($cityName);

            if ($coordinates === null) {
                return response()->json(['message' => 'City not found or unable to geocode.'], 404);
            }

            return response()->json($coordinates);

        } catch (ValidationException $e) {
            // Return validation errors
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Log general errors and return a generic server error message
            
            // Log::error('Geocoding controller error', ['exception' => $e]); // Uncomment if needed
            return response()->json(['message' => 'An unexpected error occurred.'], 500);
        }
    }
}
