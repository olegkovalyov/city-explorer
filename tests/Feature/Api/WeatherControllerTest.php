<?php

namespace Tests\Feature\Api;

use App\Contracts\Services\WeatherServiceInterface;
use App\Data\GetWeatherData;
use App\Enums\ErrorCode;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Requests\GetWeatherRequest;
use App\Support\Result;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class WeatherControllerTest extends TestCase
{
    use WithFaker;
    
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testIndexHandlesWeatherApiError()
    {
        // Mock the weather service to return an error
        $mockWeatherService = Mockery::mock(WeatherServiceInterface::class);
        $mockWeatherService->shouldReceive('getCurrentWeather')
            ->once()
            ->andReturn(Result::failure(
                ErrorCode::WEATHER_API_ERROR,
                'Weather API returned an error',
                ['status' => 400, 'message' => 'Bad Request']
            ));

        // Create controller with mocked service
        $controller = new WeatherController($mockWeatherService);

        // Mock the request
        $request = Mockery::mock(GetWeatherRequest::class);
        
        // Set expectation that the validated method will be called,
        // but the exact values don't matter as we've already configured the service
        // to return an error regardless of input data
        $request->shouldReceive('validated')->andReturn(['city' => 'Moscow']);
        
        // Call the controller method
        $response = $controller->index($request);

        // Check the result
        $this->assertEquals(424, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Weather API returned an error', $responseData['message']);
        $this->assertEquals(ErrorCode::WEATHER_API_ERROR->value, $responseData['code']);
    }

    public function testIndexHandlesUnexpectedError()
    {
        // Mock the weather service (won't be called due to exception)
        $mockWeatherService = Mockery::mock(WeatherServiceInterface::class);

        // Create controller with mocked service
        $controller = new WeatherController($mockWeatherService);

        // Mock the request that will throw an exception
        $request = Mockery::mock(GetWeatherRequest::class);
        $request->shouldReceive('validated')->andThrow(new \Exception('Unexpected test error'));

        // Call the controller method
        $response = $controller->index($request);

        // Check the result
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(ErrorCode::UNEXPECTED_ERROR->value, $responseData['code']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 