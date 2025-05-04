<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class PlaceData extends Data
{
    public function __construct(
        public string $id, // Use fsq_id as the primary ID for the frontend representation
        public ?string $fsqId, // Keep the original fsq_id as well if needed
        public string $name,
        public string $address,
        public string $category,
        public ?string $categoryIcon,
        /** @var string[] */
        public array $photos,
        public ?array $location, // Keep the raw location object if needed by frontend maps
    ) {}
}
