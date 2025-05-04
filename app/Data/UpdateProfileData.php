<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;

class UpdateProfileData extends Data
{
    public function __construct(
        public User $user, // The user whose profile is being updated
        public string $name,
        public string $email
    ) {}
}
