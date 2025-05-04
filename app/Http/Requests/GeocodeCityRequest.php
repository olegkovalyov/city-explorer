<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeocodeCityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For API requests, authorization is typically handled
        // by middleware (e.g., Sanctum), so we can return true here.
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
            'city' => 'required|string|max:255',
        ];
    }
}
