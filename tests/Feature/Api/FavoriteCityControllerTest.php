<?php

namespace Tests\Feature\Api;

use App\Contracts\Services\UserCityServiceInterface;
use App\Data\DeleteFavoriteCityData;
use App\Data\GetFavoriteCitiesData;
use App\Data\StoreFavoriteCityData;
use App\Enums\ErrorCode;
use App\Http\Controllers\Api\FavoriteCityController;
use App\Models\FavoriteCity;
use App\Models\User;
use App\Support\Result;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class FavoriteCityControllerTest extends TestCase
{
    use WithFaker;
    
    private UserCityServiceInterface $mockCityService;
    private FavoriteCityController $controller;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем реального пользователя с id=1 для тестов
        $this->user = new User();
        $this->user->id = 1;
        
        // Создаем мок сервиса
        $this->mockCityService = Mockery::mock(UserCityServiceInterface::class);
        
        // Создаем контроллер с моканным сервисом
        $this->controller = new FavoriteCityController($this->mockCityService);
    }

    public function testIndexReturnsUserFavoriteCities()
    {
        // Создаем фиктивные города
        $cities = collect([
            new FavoriteCity([
                'id' => 1,
                'user_id' => 1,
                'city_name' => 'Berlin',
                'latitude' => 52.5200,
                'longitude' => 13.4050,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            new FavoriteCity([
                'id' => 2,
                'user_id' => 1,
                'city_name' => 'Paris',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
        ]);
        
        // Настраиваем мок для метода getFavoriteCities
        $this->mockCityService->shouldReceive('getFavoriteCities')
            ->once()
            ->withArgs(function (GetFavoriteCitiesData $data) {
                return $data->user->id === $this->user->id;
            })
            ->andReturn(Result::success($cities));

        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-cities', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Act
        $response = $this->controller->index($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData);
        $this->assertEquals('Berlin', $responseData[0]['city_name']);
        $this->assertEquals('Paris', $responseData[1]['city_name']);
    }

    public function testIndexReturnsEmptyArrayWhenUserHasNoCities()
    {
        // Настраиваем мок для метода getFavoriteCities
        $this->mockCityService->shouldReceive('getFavoriteCities')
            ->once()
            ->withArgs(function (GetFavoriteCitiesData $data) {
                return $data->user->id === $this->user->id;
            })
            ->andReturn(Result::success(collect([])));

        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-cities', 'GET');
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

    public function testStoreCreatesFavoriteCity()
    {
        // Создаем фиктивный город
        $createdCity = new FavoriteCity([
            'id' => 1,
            'user_id' => 1,
            'city_name' => 'Berlin',
            'latitude' => 52.5200,
            'longitude' => 13.4050,
        ]);
        $createdCity->id = 1;
        $createdCity->created_at = now();
        $createdCity->updated_at = now();
        $createdCity->wasRecentlyCreated = true;
        
        // Настраиваем StoreFavoriteCityRequest с валидированными данными
        $request = Mockery::mock(\App\Http\Requests\StoreFavoriteCityRequest::class);
        $request->shouldReceive('user')->andReturn($this->user);
        $request->shouldReceive('validated')->andReturn([
            'city_name' => 'Berlin',
            'latitude' => 52.5200,
            'longitude' => 13.4050,
        ]);
        
        // Настраиваем мок для метода storeFavoriteCity
        $this->mockCityService->shouldReceive('storeFavoriteCity')
            ->once()
            ->withArgs(function (StoreFavoriteCityData $data) {
                return $data->user->id === $this->user->id &&
                       $data->cityName === 'Berlin' &&
                       $data->latitude === 52.5200 &&
                       $data->longitude === 13.4050;
            })
            ->andReturn(Result::success($createdCity));

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Berlin', $responseData['city_name']);
        $this->assertEquals(52.5200, $responseData['latitude']);
        $this->assertEquals(13.4050, $responseData['longitude']);
    }

    public function testStoreReturnsExistingCityWhenNameAlreadyExists()
    {
        // Создаем существующий город
        $existingCity = new FavoriteCity([
            'id' => 1,
            'user_id' => 1,
            'city_name' => 'London',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);
        $existingCity->id = 1;
        $existingCity->created_at = now();
        $existingCity->updated_at = now();
        $existingCity->wasRecentlyCreated = false;

        // Настраиваем StoreFavoriteCityRequest с валидированными данными
        $request = Mockery::mock(\App\Http\Requests\StoreFavoriteCityRequest::class);
        $request->shouldReceive('user')->andReturn($this->user);
        $request->shouldReceive('validated')->andReturn([
            'city_name' => 'London',
            'latitude' => 51.0000, // разные координаты
            'longitude' => -0.5000,
        ]);
        
        // Настраиваем мок для метода storeFavoriteCity
        $this->mockCityService->shouldReceive('storeFavoriteCity')
            ->once()
            ->andReturn(Result::success($existingCity));

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode()); // 200 для существующего
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('London', $responseData['city_name']);
        $this->assertEquals(51.5074, $responseData['latitude']); // оригинальные координаты
        $this->assertEquals(-0.1278, $responseData['longitude']);
    }

    public function testDestroyRemovesFavoriteCity()
    {
        // Настраиваем мок для метода deleteFavoriteCity
        $this->mockCityService->shouldReceive('deleteFavoriteCity')
            ->once()
            ->withArgs(function (DeleteFavoriteCityData $data) {
                return $data->user->id === $this->user->id && $data->cityId === 1;
            })
            ->andReturn(Result::success(true));

        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-cities/1', 'DELETE');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Act
        $response = $this->controller->destroy($request, 1);

        // Assert
        $this->assertEquals(204, $response->getStatusCode()); // No content
        $this->assertSame('{}', $response->getContent());
    }

    public function testDestroyReturns404WhenCityNotFound()
    {
        // Настраиваем мок для метода deleteFavoriteCity
        $this->mockCityService->shouldReceive('deleteFavoriteCity')
            ->once()
            ->withArgs(function (DeleteFavoriteCityData $data) {
                return $data->user->id === $this->user->id && $data->cityId === 999;
            })
            ->andReturn(Result::failure(ErrorCode::NOT_FOUND));

        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-cities/999', 'DELETE');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Act
        $response = $this->controller->destroy($request, 999);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testHandlesDatabaseErrorOnGetCities()
    {
        // Настраиваем мок на возврат ошибки
        $this->mockCityService->shouldReceive('getFavoriteCities')
            ->once()
            ->andReturn(Result::failure(ErrorCode::DATABASE_ERROR, 'Database error occurred'));
            
        // Создаем Request с пользователем
        $request = Request::create('/api/favorite-cities', 'GET');
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
        // Настраиваем StoreFavoriteCityRequest с валидированными данными
        $request = Mockery::mock(\App\Http\Requests\StoreFavoriteCityRequest::class);
        $request->shouldReceive('user')->andReturn($this->user);
        $request->shouldReceive('validated')->andReturn([
            'city_name' => 'Berlin',
            'latitude' => 52.5200,
            'longitude' => 13.4050,
        ]);
        
        // Настраиваем мок на возврат ошибки
        $this->mockCityService->shouldReceive('storeFavoriteCity')
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