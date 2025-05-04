<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFavoriteCityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming authorization is handled by middleware (e.g., auth:sanctum)
        // If specific authorization logic is needed for this request, implement it here.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'city_name' => [
                'required',
                'string',
                'max:255',
                // Ensure the city isn't already favorited by this user
                Rule::unique('favorite_cities')->where(function ($query) {
                    // Use $this->user() to get the authenticated user
                    return $query->where('user_id', $this->user()->id);
                }),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
