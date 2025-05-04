<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;

class DeleteFavoriteCityData extends Data
{
    public function __construct(
        public readonly User $user,
        public readonly int $cityId // Assuming city ID is an integer
    ) {}
}
