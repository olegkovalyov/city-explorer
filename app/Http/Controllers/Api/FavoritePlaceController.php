<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FavoritePlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class FavoritePlaceController extends Controller
{
    /**
     * Display a listing of the authenticated user's favorite places.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user(); // Get authenticated user via injected request (works with sanctum)
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            $favorites = $user->favoritePlaces()->orderBy('created_at', 'desc')->get();
            return response()->json($favorites);
        } catch (\Exception $e) {
            Log::error('Error fetching favorite places: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch favorite places.'], 500);
        }
    }

    /**
     * Store a newly created favorite place in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $validatedData = $request->validate([
                'fsq_id' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'photo_url' => 'nullable|url|max:1024',
                'category' => 'nullable|string|max:255',
                'category_icon' => 'nullable|url|max:1024',
            ]);

            // Prepare data for creation, adding user_id
            $dataToCreate = $validatedData;
            $dataToCreate['user_id'] = $user->id;

            // Use firstOrCreate to prevent duplicates based on user_id and fsq_id
            $favorite = FavoritePlace::firstOrCreate(
                ['user_id' => $user->id, 'fsq_id' => $validatedData['fsq_id']],
                $dataToCreate
            );

            // Check if the model was recently created or already existed
            if ($favorite->wasRecentlyCreated) {
                return response()->json($favorite, 201); // Return 201 Created for new resource
            } else {
                // Optionally update if it exists, or just return the existing one
                // $favorite->update($dataToCreate); // Uncomment if you want to update on subsequent requests
                return response()->json($favorite, 200); // Return 200 OK if it already existed
            }

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid data provided.', 'errors' => $e->errors()], 422);
        } catch (QueryException $e) {
             // Catch potential unique constraint violation if firstOrCreate somehow fails (though unlikely)
             Log::error('Database error adding favorite: ' . $e->getMessage());
             // Check if it's a unique constraint violation (code 23000 for SQLSTATE)
             if ($e->errorInfo[1] == 1062 || str_contains($e->getMessage(), 'UNIQUE constraint failed')) { // MySQL/SQLite specific checks
                 return response()->json(['message' => 'Place already favorited.'], 409); // 409 Conflict
             }
             return response()->json(['message' => 'Database error occurred.'], 500);
        } catch (\Exception $e) {
            Log::error('Error adding favorite place: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to add favorite place.'], 500);
        }
    }

    /**
     * Remove the specified favorite place from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $fsq_id  // Route parameter is fsq_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $fsq_id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $favorite = FavoritePlace::where('user_id', $user->id)
                                   ->where('fsq_id', $fsq_id)
                                   ->first();

            if (!$favorite) {
                return response()->json(['message' => 'Favorite place not found.'], 404);
            }

            $favorite->delete();

            return response()->json(null, 204); // 204 No Content on successful deletion
        } catch (\Exception $e) {
            Log::error('Error deleting favorite place: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete favorite place.'], 500);
        }
    }
}
