<?php

namespace Database\Factories;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'key' => $this->faker->unique()->word,
            'min_stock_level' => $this->faker->numberBetween(10000, 50000),
            'stock_level' => $this->faker->numberBetween(1000, 50000),
            'low_stock_notified' => false,
        ];
    }
}
