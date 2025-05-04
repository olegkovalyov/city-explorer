<?php

namespace App\Contracts\Services;

use App\Data\GetWeatherData;
use App\Support\Result;

interface WeatherServiceInterface
{
    public function getCurrentWeather(GetWeatherData $data): Result;
}
