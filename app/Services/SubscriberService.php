<?php

namespace App\Services;

use App\Enums\ErrorCode;
use App\Models\Subscriber;
use App\Support\Result;
use Illuminate\Support\Facades\Log;

class SubscriberService
{
    public function subscribe(string $email, bool $status): Result
    {
        $data = [
            'email' => $email,
            'status' => $status,
        ];
        try {
            $subscriber = Subscriber::firstOrCreate($data);
            return Result::success($subscriber);
        } catch (\Exception $exception) {
            Log::error('something went wrong: '.$exception->getMessage());
            return Result::failure(ErrorCode::DATABASE_ERROR, $exception->getMessage());
        }
    }
}
