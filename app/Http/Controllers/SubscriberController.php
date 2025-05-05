<?php

namespace App\Http\Controllers;

use App\Contracts\Services\SubscriberServiceInterface;
use App\Http\Requests\SubscriberRequest;

class SubscriberController extends Controller
{
    public function __construct(
        private readonly SubscriberServiceInterface $subscriberService
    ) {
    }

    public function store(SubscriberRequest $request)
    {
        $email = $request->email;
        $status = $request->status;
        $result = $this->subscriberService->subscribe($email, $status);
        if ($result->isSuccess()) {
            return response()->json(['success' => true, 'data' => $result->getValue()]);
        }
        return response()->json(['success' => false, 'data' => null]);
    }
}
