<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchPlacesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * For public API endpoints like search, this is typically true.
     * If specific authorization is needed later, implement it here.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Assuming search is public, otherwise add user authorization logic
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'], // Keep 'sometimes'
        ];
    }

    /**
     * Get validated data with defaults applied.
     * Specifically handles the 'limit' default value.
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();
        // Apply default limit if not provided or invalid (though rules should prevent invalid)
        if (!isset($validated['limit'])) {
            $validated['limit'] = 6; // Default limit
        }
        return $validated;
    }
}
