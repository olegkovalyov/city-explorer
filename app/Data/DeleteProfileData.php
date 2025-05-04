<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;

class DeleteProfileData extends Data
{
    public function __construct(
        public User $user, // The user whose profile is being deleted
        public string $password // The current password provided for verification
    ) {}
}
