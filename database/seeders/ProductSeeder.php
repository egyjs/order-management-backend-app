<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $burger = Product::create(['name' => 'Burger']);

        $ingredients = Ingredient::whereIn('key', ['beef', 'cheese', 'onion'])
            ->pluck('id', 'key');

        $burger->ingredients()->attach([
            $ingredients['beef'] => ['amount' => 150], // 150g
            $ingredients['cheese'] => ['amount' => 30], // 30g Cheese
            $ingredients['onion'] => ['amount' => 20], // 20g Onion
        ]);
    }
}
