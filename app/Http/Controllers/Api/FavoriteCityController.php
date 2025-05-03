<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FavoriteCity; // Import FavoriteCity model
use Illuminate\Http\Request; // Import Request
use Illuminate\Http\JsonResponse; // For type hinting
use Illuminate\Support\Facades\Auth; // To get authenticated user
use Illuminate\Validation\Rule; // For unique rule

class FavoriteCityController extends Controller
{
    /**
     * Display a listing of the authenticated user's favorite cities.
     */
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()->favoriteCities()->orderBy('city_name')->get();
        // Alternatively, if you didn't define the relationship in User model yet:
        // $favorites = FavoriteCity::where('user_id', $request->user()->id)->orderBy('city_name')->get();

        return response()->json($favorites);
    }

    /**
     * Store a newly created favorite city in storage for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_name' => [
                'required',
                'string',
                'max:255',
                 // Ensure the city isn't already favorited by this user
                Rule::unique('favorite_cities')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id);
                }),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $favorite = $request->user()->favoriteCities()->create($validated);
        // Alternatively:
        // $favorite = FavoriteCity::create([
        //     'user_id' => $request->user()->id,
        //     'city_name' => $validated['city_name'],
        //     'latitude' => $validated['latitude'],
        //     'longitude' => $validated['longitude'],
        // ]);

        return response()->json($favorite, 201); // 201 Created status
    }

    /**
     * Display the specified resource.
     * We don't need this for now.
     */
    public function show(string $id)
    {
        // Not implemented
        return response()->json(['message' => 'Not Implemented'], 501);
    }

    /**
     * Update the specified resource in storage.
     * We don't need this for now.
     */
    public function update(Request $request, string $id)
    {
        // Not implemented
        return response()->json(['message' => 'Not Implemented'], 501);
    }

    /**
     * Remove the specified favorite city from storage for the authenticated user.
     */
    public function destroy(Request $request, FavoriteCity $favoriteCity): JsonResponse
    {
        // Authorization: Check if the authenticated user owns this favorite city
        if ($request->user()->id !== $favoriteCity->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $favoriteCity->delete();

        return response()->json(null, 204); // 204 No Content status
    }
}
