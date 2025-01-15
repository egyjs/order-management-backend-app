<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $beef_stock = 20000; // 20kg
        $cheese_stock = 5000; // 5kg
        $onion_stock = 1000; // 1kg

        $ingredients = [
            ['name' => 'Beef', 'key' => 'beef', 'stock_level' => $beef_stock, 'min_stock_level' => $beef_stock],
            ['name' => 'Cheese', 'key' => 'cheese', 'stock_level' => $cheese_stock, 'min_stock_level' => $cheese_stock],
            ['name' => 'Onion', 'key' => 'onion', 'stock_level' => $onion_stock, 'min_stock_level' => $onion_stock],
        ];

        Ingredient::insert($ingredients);
    }
}
