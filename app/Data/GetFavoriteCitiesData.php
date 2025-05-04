<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;

class GetFavoriteCitiesData extends Data
{
    public function __construct(
        public readonly User $user
    ) {}
}
