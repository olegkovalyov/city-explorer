<?php

namespace Tests\Feature\Api;

use App\Contracts\Services\ExternalPlaceSearchInterface;
use App\Contracts\Services\PlaceServiceInterface;
use App\Enums\ErrorCode;
use App\Http\Controllers\Api\PlacesController;
use App\Support\Result;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class PlacesControllerTest extends TestCase
{
    use WithFaker;
    
    private ExternalPlaceSearchInterface $mockFoursquareService;
    private PlaceServiceInterface $mockPlaceService;
    private PlacesController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем моки сервисов
        $this->mockFoursquareService = Mockery::mock(ExternalPlaceSearchInterface::class);
        $this->mockPlaceService = Mockery::mock(PlaceServiceInterface::class);
        
        // Создаем контроллер с моканными сервисами
        $this->controller = new PlacesController(
            $this->mockFoursquareService,
            $this->mockPlaceService
        );
    }

    public function testIndexReturnsPlaces()
    {
        // Подготавливаем тестовые данные для результата поиска мест
        $foursquareResults = [
            [
                'fsq_id' => '123abc',
                'name' => 'Red Square',
                'location' => [
                    'address' => 'Red Square',
                    'formatted_address' => 'Red Square, Moscow, Russia',
                    'country' => 'RU',
                    'locality' => 'Moscow',
                    'latitude' => 55.7539,
                    'longitude' => 37.6208,
                ],
                'categories' => [
                    [
                        'name' => 'Plaza',
                        'icon' => [
                            'prefix' => 'https://ss3.4sqi.net/img/categories_v2/parks_outdoors/plaza_',
                            'suffix' => '.png'
                        ]
                    ]
                ],
                'photos' => [
                    [
                        'prefix' => 'https://fastly.4sqi.net/img/general/',
                        'suffix' => '/50126537_8GIMn0G5XF9Ns6JMlvW8wqjPCFbGNI36W1NeH3poU_A.jpg'
                    ]
                ]
            ],
            [
                'fsq_id' => '456def',
                'name' => 'St. Basil\'s Cathedral',
                'location' => [
                    'address' => 'Red Square, 2',
                    'formatted_address' => 'Red Square, 2, Moscow, Russia',
                    'country' => 'RU',
                    'locality' => 'Moscow',
                    'latitude' => 55.7525,
                    'longitude' => 37.6231,
                ],
                'categories' => [
                    [
                        'name' => 'Church',
                        'icon' => [
                            'prefix' => 'https://ss3.4sqi.net/img/categories_v2/building/religious_church_',
                            'suffix' => '.png'
                        ]
                    ]
                ],
                'photos' => [
                    [
                        'prefix' => 'https://fastly.4sqi.net/img/general/',
                        'suffix' => '/10299162_fV2A5cs5gj6Y8JXEFbgKhaFvUF05-M9VFKnXw5Czl-g.jpg'
                    ]
                ]
            ]
        ];

        // Настраиваем SearchPlacesRequest с валидированными данными
        $request = Mockery::mock(\App\Http\Requests\SearchPlacesRequest::class);
        $request->shouldReceive('validatedWithDefaults')->andReturn([
            'latitude' => 55.7522,
            'longitude' => 37.6156,
            'limit' => 10
        ]);
        
        // Настраиваем мок для FoursquareService
        $this->mockFoursquareService->shouldReceive('searchPlaces')
            ->once()
            ->with(55.7522, 37.6156)
            ->andReturn(Result::success($foursquareResults));

        // Act
        $response = $this->controller->index($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('places', $responseData);
        $this->assertCount(2, $responseData['places']);
        
        $place1 = $responseData['places'][0];
        $this->assertEquals('123abc', $place1['fsq_id'] ?? $place1['id']);
        $this->assertEquals('Red Square', $place1['name']);
        $this->assertEquals('Red Square, Moscow, Russia', $place1['address']);
        $this->assertEquals('Plaza', $place1['category']);
    }

    public function testIndexHandlesApiFailure()
    {
        // Настраиваем SearchPlacesRequest с валидированными данными
        $request = Mockery::mock(\App\Http\Requests\SearchPlacesRequest::class);
        $request->shouldReceive('validatedWithDefaults')->andReturn([
            'latitude' => 55.7522,
            'longitude' => 37.6156,
            'limit' => 10
        ]);
        
        // Симулируем ошибку API Foursquare
        $this->mockFoursquareService->shouldReceive('searchPlaces')
            ->once()
            ->andReturn(Result::failure(
                ErrorCode::FOURSQUARE_API_ERROR,
                'Foursquare API returned an error',
                ['status' => 400, 'message' => 'Bad Request']
            ));

        // Act
        $response = $this->controller->index($request);

        // Assert
        $this->assertEquals(502, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Foursquare API returned an error', $responseData['message']);
    }

    public function testShowReturnsPlaceDetails()
    {
        // Подготавливаем тестовые данные для детальной информации о месте
        $placeDetails = [
            'fsq_id' => '123abc',
            'name' => 'Red Square',
            'location' => [
                'address' => 'Red Square',
                'formatted_address' => 'Red Square, Moscow, Russia',
                'country' => 'RU',
                'locality' => 'Moscow',
                'latitude' => 55.7539,
                'longitude' => 37.6208,
            ],
            'categories' => [
                [
                    'name' => 'Plaza',
                    'icon' => [
                        'prefix' => 'https://ss3.4sqi.net/img/categories_v2/parks_outdoors/plaza_',
                        'suffix' => '.png'
                    ]
                ]
            ],
            'photos' => [
                [
                    'prefix' => 'https://fastly.4sqi.net/img/general/',
                    'suffix' => '/50126537_8GIMn0G5XF9Ns6JMlvW8wqjPCFbGNI36W1NeH3poU_A.jpg'
                ],
                [
                    'prefix' => 'https://fastly.4sqi.net/img/general/',
                    'suffix' => '/1234567_AnotherPhotoIDHere.jpg'
                ]
            ]
        ];

        // Настраиваем мок для FoursquareService
        $this->mockFoursquareService->shouldReceive('getPlaceDetails')
            ->once()
            ->with('123abc')
            ->andReturn(Result::success($placeDetails));

        // Act
        $response = $this->controller->show('123abc');

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $place = json_decode($response->getContent(), true);
        
        $this->assertEquals('123abc', $place['fsq_id'] ?? $place['id']);
        $this->assertEquals('Red Square', $place['name']);
        $this->assertEquals('Red Square, Moscow, Russia', $place['address']);
        $this->assertEquals('Plaza', $place['category']);
        $this->assertCount(2, $place['photos']);
        $this->assertStringContainsString('https://fastly.4sqi.net/img/general/', $place['photos'][0]);
    }

    public function testShowHandlesEmptyFsqId()
    {
        // Act
        $response = $this->controller->show('');

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Foursquare ID is required.', $responseData['message']);
    }

    public function testShowHandlesNotFound()
    {
        // Симулируем, что место не найдено в API
        $this->mockFoursquareService->shouldReceive('getPlaceDetails')
            ->once()
            ->with('nonexistent')
            ->andReturn(Result::failure(
                ErrorCode::NOT_FOUND,
                'Place not found',
                ['status' => 404, 'message' => 'Not Found']
            ));

        // Act
        $response = $this->controller->show('nonexistent');

        // Assert
        $this->assertEquals(500, $response->getStatusCode()); // Default case in match expression
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Place not found', $responseData['message']);
    }

    public function testShowHandlesApiUnavailable()
    {
        // Симулируем, что API недоступен
        $this->mockFoursquareService->shouldReceive('getPlaceDetails')
            ->once()
            ->with('123abc')
            ->andReturn(Result::failure(
                ErrorCode::FOURSQUARE_API_UNAVAILABLE,
                'Foursquare API is currently unavailable',
                ['status' => 503]
            ));

        // Act
        $response = $this->controller->show('123abc');

        // Assert
        $this->assertEquals(503, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Foursquare API is currently unavailable', $responseData['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 