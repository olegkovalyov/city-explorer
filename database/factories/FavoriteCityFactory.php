<?php

namespace Database\Factories;

use App\Models\FavoriteCity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FavoriteCityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FavoriteCity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'city_name' => $this->faker->city(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
        ];
    }
} 