<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // For logging errors
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WeatherController extends Controller
{
    /**
     * Fetch current weather data for given coordinates.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Invalid coordinates provided.', 'errors' => $validator->errors()], 422);
            }

            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            $apiKey = config('services.openweathermap.key') ?: env('OPENWEATHERMAP_API_KEY');

            if (!$apiKey) {
                Log::error('OpenWeatherMap API key is not configured.');
                return response()->json(['message' => 'Weather service configuration error.'], 500);
            }

            $apiUrl = 'https://api.openweathermap.org/data/2.5/weather';

            $response = Http::timeout(10)->get($apiUrl, [
                'lat' => $latitude,
                'lon' => $longitude,
                'appid' => $apiKey,
                'units' => 'metric', // Get temperature in Celsius
            ]);

            if ($response->failed()) {
                Log::error('OpenWeatherMap API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);
                // Try to return a meaningful error message from OpenWeatherMap if possible
                $errorBody = $response->json();
                $errorMessage = $errorBody['message'] ?? 'Failed to fetch weather data.';
                return response()->json(['message' => $errorMessage], $response->status());
            }

            $data = $response->json();

            // Extract relevant data
            $weatherInfo = [
                'temperature' => $data['main']['temp'] ?? null,
                'feels_like' => $data['main']['feels_like'] ?? null,
                'description' => $data['weather'][0]['description'] ?? 'N/A',
                'icon_code' => $data['weather'][0]['icon'] ?? null,
                'icon_url' => isset($data['weather'][0]['icon'])
                                ? 'https://openweathermap.org/img/wn/' . $data['weather'][0]['icon'] . '@2x.png'
                                : null,
                'city_name' => $data['name'] ?? null, // City name returned by OWM
                'humidity' => $data['main']['humidity'] ?? null,
                'wind_speed' => $data['wind']['speed'] ?? null,
            ];

            return response()->json($weatherInfo);

        } catch (\Exception $e) {
            Log::error('Error in WeatherController@index: ' . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred while fetching weather data.'], 500);
        }
    }
}
