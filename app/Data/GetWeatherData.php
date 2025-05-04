<?php

namespace App\Data;

use App\Http\Requests\GetWeatherRequest;
use Spatie\LaravelData\Data;

class GetWeatherData extends Data
{
    public function __construct(
        public readonly ?string $city,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
    ) {}

    public static function fromRequest(GetWeatherRequest $request): static
    {
        $validated = $request->validated();

        return new self(
            city: $validated['city'] ?? null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
        );
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function hasCity(): bool
    {
        return !empty($this->city);
    }
}
