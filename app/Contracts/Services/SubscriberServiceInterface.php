<?php

namespace App\Contracts\Services;

use App\Support\Result;

interface SubscriberServiceInterface
{
    public function subscribe(string $email, bool $status): Result;
}
