<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\From;
use Spatie\LaravelData\Attributes\Validation\Rule;

class StoreFavoriteCityData extends Data
{
    public function __construct(
        public readonly User $user,
        #[Rule(['required', 'string', 'max:255'])]
        #[From('city_name')] // Map from request field
        public readonly string $cityName,
        #[Rule(['required', 'numeric', 'between:-90,90'])]
        public readonly float $latitude,
        #[Rule(['required', 'numeric', 'between:-180,180'])]
        public readonly float $longitude
    ) {}

    /**
     * Create DTO from user and validated request data.
     *
     * @param User $user
     * @param array $validatedData
     * @return self
     */
    public static function fromValidated(User $user, array $validatedData): self
    {
        return new self(
            user: $user,
            cityName: $validatedData['city_name'],
            latitude: (float) $validatedData['latitude'],
            longitude: (float) $validatedData['longitude']
        );
    }
}
