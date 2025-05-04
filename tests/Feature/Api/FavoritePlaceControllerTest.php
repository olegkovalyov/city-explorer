<?php

namespace Tests\Feature\Api;

use App\Contracts\Services\PlaceServiceInterface;
use App\Data\DeleteFavoritePlaceData;
use App\Data\GetFavoritePlacesData;
use App\Data\StoreFavoritePlaceData;
use App\Enums\ErrorCode;
use App\Http\Controllers\Api\FavoritePlaceController;
use App\Models\FavoritePlace;
use App\Models\User;
use App\Support\Result;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class FavoritePlaceControllerTest extends TestCase
{
    use WithFaker;
    
    private PlaceServiceInterface $mockPlaceService;
    private FavoritePlaceController $controller;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем реального пользователя с id=1 для тестов
        $this->user = new User();
        $this->user->id = 1;
        
        // Создаем мок сервиса
        $this->mockPlaceService = Mockery::mock(PlaceServiceInterface::class);
        
        // Создаем контроллер с моканным сервисом
        $this->controller = new FavoritePlaceController($this->mockPlaceService);
    }

    public function testIndexReturnsUserFavoritePlaces()
    {
        // Создаем фиктивные места
        $places = collect([
            new FavoritePlace([
                'id' => 1,
                'user_id' => 1,
                'fsq_id' => '123abc',
                'name' => 'Cafe Москва',
                'address' => 'Тверская 1',
                'latitude' => 55.7558,
                'longitude' => 37.6173,
                'photo_url' => 'https://example.com/photo1.jpg',
                'category' => 'Cafe',
                'category_icon' => 'https://example.com/icon1.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            new FavoritePlace([
                'id' => 2,
                'user_id' => 1,
                'fsq_id' => '456def',
                'name' => 'Библиотека им. Ленина',
                'address' => 'Воздвиженка 3/5',
                'latitude' => 55.7517,
                'longitude' => 37.6103,
                'photo_url' => 'https://example.com/photo2.jpg',
                'category' => 'Library',
                'category_icon' => 'https://example.com/icon2.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
        ]);
        
        // Настраиваем мок для метода getFavoritePlaces
        $this->mockPlaceService->shouldReceive('getFavoritePlaces')
            ->once()
            ->withArgs(function (GetFavoritePlacesData $data) {
                return $data->user->id === $this->user->id;
            })
            ->andReturn(Result::success($places));

        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-places', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Act
        $response = $this->controller->index($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData);
        $this->assertEquals('Cafe Москва', $responseData[0]['name']);
        $this->assertEquals('Библиотека им. Ленина', $responseData[1]['name']);
    }

    public function testIndexReturnsEmptyArrayWhenUserHasNoPlaces()
    {
        // Настраиваем мок для метода getFavoritePlaces
        $this->mockPlaceService->shouldReceive('getFavoritePlaces')
            ->once()
            ->withArgs(function (GetFavoritePlacesData $data) {
                return $data->user->id === $this->user->id;
            })
            ->andReturn(Result::success(collect([])));

        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-places', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Act
        $response = $this->controller->index($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEmpty($responseData);
    }

    public function testStoreCreatesFavoritePlace()
    {
        // Создаем фиктивное место
        $createdPlace = new FavoritePlace([
            'user_id' => 1,
            'fsq_id' => '123abc',
            'name' => 'Red Square',
            'address' => 'Red Square, Moscow',
            'latitude' => 55.7539,
            'longitude' => 37.6208,
            'photo_url' => 'https://example.com/redsquare.jpg',
            'category' => 'Landmark',
            'category_icon' => 'https://example.com/landmark.jpg',
        ]);
        $createdPlace->id = 1;
        $createdPlace->created_at = now();
        $createdPlace->updated_at = now();
        $createdPlace->wasRecentlyCreated = true;
        
        // Настраиваем StoreFavoritePlaceRequest с валидированными данными
        $request = Mockery::mock(\App\Http\Requests\StoreFavoritePlaceRequest::class);
        $request->shouldReceive('user')->andReturn($this->user);
        $request->shouldReceive('validated')->andReturn([
            'fsq_id' => '123abc',
            'name' => 'Red Square',
            'address' => 'Red Square, Moscow',
            'latitude' => 55.7539,
            'longitude' => 37.6208,
            'photo_url' => 'https://example.com/redsquare.jpg',
            'category' => 'Landmark',
            'category_icon' => 'https://example.com/landmark.jpg',
        ]);
        
        // Настраиваем мок для метода storeFavoritePlace
        $this->mockPlaceService->shouldReceive('storeFavoritePlace')
            ->once()
            ->withArgs(function (StoreFavoritePlaceData $data) {
                return $data->user->id === $this->user->id &&
                       $data->fsqId === '123abc' &&
                       $data->name === 'Red Square' &&
                       $data->address === 'Red Square, Moscow' &&
                       $data->latitude === 55.7539 &&
                       $data->longitude === 37.6208 &&
                       $data->photoUrl === 'https://example.com/redsquare.jpg' &&
                       $data->category === 'Landmark' &&
                       $data->categoryIcon === 'https://example.com/landmark.jpg';
            })
            ->andReturn(Result::success($createdPlace));

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Red Square', $responseData['name']);
        $this->assertEquals('Red Square, Moscow', $responseData['address']);
        $this->assertEquals(55.7539, $responseData['latitude']);
        $this->assertEquals(37.6208, $responseData['longitude']);
    }

    public function testStoreReturnsExistingPlaceWhenFsqIdAlreadyExists()
    {
        // Создаем существующее место
        $existingPlace = new FavoritePlace([
            'user_id' => 1,
            'fsq_id' => '123abc',
            'name' => 'Red Square',
            'address' => 'Red Square, Moscow',
            'latitude' => 55.7539,
            'longitude' => 37.6208,
            'photo_url' => 'https://example.com/redsquare.jpg',
            'category' => 'Landmark',
            'category_icon' => 'https://example.com/landmark.jpg',
        ]);
        $existingPlace->id = 1;
        $existingPlace->created_at = now();
        $existingPlace->updated_at = now();
        $existingPlace->wasRecentlyCreated = false;

        // Настраиваем StoreFavoritePlaceRequest с валидированными данными
        $request = Mockery::mock(\App\Http\Requests\StoreFavoritePlaceRequest::class);
        $request->shouldReceive('user')->andReturn($this->user);
        $request->shouldReceive('validated')->andReturn([
            'fsq_id' => '123abc',
            'name' => 'Red Square Updated', // измененное имя
            'address' => 'Updated Address',
            'latitude' => 55.7540,
            'longitude' => 37.6209,
            'photo_url' => 'https://example.com/new.jpg',
            'category' => 'Tourist Attraction',
            'category_icon' => 'https://example.com/newicon.jpg',
        ]);
        
        // Настраиваем мок для метода storeFavoritePlace
        $this->mockPlaceService->shouldReceive('storeFavoritePlace')
            ->once()
            ->andReturn(Result::success($existingPlace));

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode()); // 200 для существующего
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Red Square', $responseData['name']); // оригинальное имя
        $this->assertEquals('Red Square, Moscow', $responseData['address']); // оригинальный адрес
    }

    public function testDestroyRemovesFavoritePlace()
    {
        // Настраиваем мок для метода deleteFavoritePlace
        $this->mockPlaceService->shouldReceive('deleteFavoritePlace')
            ->once()
            ->withArgs(function (DeleteFavoritePlaceData $data) {
                return $data->user->id === $this->user->id && $data->fsqId === '123abc';
            })
            ->andReturn(Result::success(true));

        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-places/123abc', 'DELETE');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Act
        $response = $this->controller->destroy($request, '123abc');

        // Assert
        $this->assertEquals(204, $response->getStatusCode()); // No content
        $this->assertSame('{}', $response->getContent());
    }

    public function testDestroyReturns404WhenPlaceNotFound()
    {
        // Настраиваем мок для метода deleteFavoritePlace
        $this->mockPlaceService->shouldReceive('deleteFavoritePlace')
            ->once()
            ->withArgs(function (DeleteFavoritePlaceData $data) {
                return $data->user->id === $this->user->id && $data->fsqId === 'nonexistent';
            })
            ->andReturn(Result::failure(ErrorCode::NOT_FOUND));

        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-places/nonexistent', 'DELETE');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Act
        $response = $this->controller->destroy($request, 'nonexistent');

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testHandlesDatabaseErrorOnGetPlaces()
    {
        // Настраиваем мок на возврат ошибки
        $this->mockPlaceService->shouldReceive('getFavoritePlaces')
            ->once()
            ->andReturn(Result::failure(ErrorCode::DATABASE_ERROR, 'Database error occurred'));
            
        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-places', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Act
        $response = $this->controller->index($request);
            
        // Assert
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Database error occurred', $responseData['message']);
    }
    
    public function testHandlesUnexpectedErrorOnStore()
    {
        // Настраиваем StoreFavoritePlaceRequest с валидированными данными
        $request = Mockery::mock(\App\Http\Requests\StoreFavoritePlaceRequest::class);
        $request->shouldReceive('user')->andReturn($this->user);
        $request->shouldReceive('validated')->andReturn([
            'fsq_id' => '123abc',
            'name' => 'Red Square',
            'address' => 'Red Square, Moscow',
            'latitude' => 55.7539,
            'longitude' => 37.6208,
            'photo_url' => 'https://example.com/redsquare.jpg',
            'category' => 'Landmark',
            'category_icon' => 'https://example.com/landmark.jpg',
        ]);
        
        // Настраиваем мок на возврат ошибки
        $this->mockPlaceService->shouldReceive('storeFavoritePlace')
            ->once()
            ->andReturn(Result::failure(ErrorCode::UNEXPECTED_ERROR, 'Unexpected error occurred'));
            
        // Act
        $response = $this->controller->store($request);
            
        // Assert
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unexpected error occurred', $responseData['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 