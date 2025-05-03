<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)] 
class DeleteFavoritePlaceData extends Data
{
    public function __construct(
        public readonly User $user,
        public readonly string $fsqId,
    ) {}
}
