<?php

namespace App\Contracts\Services;

use App\Data\UpdateProfileData;
use App\Support\Result;

interface UserProfileServiceInterface
{
    public function updateProfile(UpdateProfileData $data): Result;
}
