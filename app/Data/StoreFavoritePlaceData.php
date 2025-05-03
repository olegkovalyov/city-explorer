<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)] // Automatically map snake_case keys from validated data
class StoreFavoritePlaceData extends Data
{
    public function __construct(
        public readonly User $user,
        public readonly string $fsqId,
        public readonly string $name,
        public readonly ?string $address,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?string $photoUrl,
        public readonly ?string $category,
        public readonly ?string $categoryIcon,
    ) {}

    // Optional: Add static creator to combine user and validated data easily
    public static function fromValidated(User $user, array $validatedData): self
    {
        return new self(
            user: $user,
            fsqId: $validatedData['fsq_id'],
            name: $validatedData['name'],
            address: $validatedData['address'] ?? null,
            latitude: isset($validatedData['latitude']) ? (float) $validatedData['latitude'] : null,
            longitude: isset($validatedData['longitude']) ? (float) $validatedData['longitude'] : null,
            photoUrl: $validatedData['photo_url'] ?? null,
            category: $validatedData['category'] ?? null,
            categoryIcon: $validatedData['category_icon'] ?? null,
        );
    }
}
