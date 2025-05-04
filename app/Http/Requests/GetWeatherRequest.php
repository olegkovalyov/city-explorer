<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetWeatherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming any authenticated user can request weather
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'city' => [
                'required_without_all:latitude,longitude',
                'nullable',
                'string',
                'min:2',
            ],
            'latitude' => [
                'required_without:city',
                'required_with:longitude',
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required_without:city',
                'required_with:latitude',
                'nullable',
                'numeric',
                'between:-180,180',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'city.required_without_all' => 'Either city or both latitude and longitude must be provided.',
            'latitude.required_without' => 'Latitude is required when city is not provided.',
            'longitude.required_without' => 'Longitude is required when city is not provided.',
            'latitude.required_with' => 'Latitude must be provided together with longitude.',
            'longitude.required_with' => 'Longitude must be provided together with latitude.',
        ];
    }
}
