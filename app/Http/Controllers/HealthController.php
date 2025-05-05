<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HealthController extends Controller
{
    /**
     * Simple health check endpoint that returns 200 OK.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function check()
    {
        return response()->json(['status' => 'OK'], 200);
    }
}
