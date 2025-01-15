<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Product;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderFeatureTest extends TestCase
{
    public function testOrderCreationSuccess()
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/orders', [
            'products' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'status',
                'message',
                'data' => [
                    'id',
                    'created_at',
                    'products' => [
                        [
                            'id',
                            'name',
                            'qty',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('data.id'),
        ]);

        $product->ingredients->each(function (Ingredient $ingredient) {
            $this->assertDatabaseHas('ingredients', [
                'id' => $ingredient->id,
                'stock_level' => $ingredient->min_stock_level - $ingredient->pivot->amount,
            ]);
        });
    }

    public function testOrderCreationFailsForInvalidProductId()
    {
        $response = $this->postJson('/api/orders', [
            'products' => [
                ['product_id' => 'invalid-product-id', 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['products.0.product_id']);
    }

    public function testOrderCreationFailsDueToInsufficientStock()
    {
        $product = Product::first();
        // Update the stock of the first ingredient to a value less than the required amount
        $ingredient = $product->ingredients->first();
        $ingredient->stock_level = $ingredient->pivot->amount - 1;
        $ingredient->save();

        $response = $this->postJson('/api/orders', [
            'products' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'errors',
            ]);
    }

    public function testOrderCreationFailsForInvalidQuantity()
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/orders', [
            'products' => [
                ['product_id' => $product->id, 'qty' => 'invalid-qty'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['products.0.qty']);
    }

    public function testLowStockNotificationIsSent()
    {
        Notification::fake();

        $product = Product::factory()->create();
        $ingredient = Ingredient::factory()->create(['stock_level' => 100, 'min_stock_level' => 120]);

        $product->ingredients()->attach($ingredient->id, ['amount' => 50]);

        $response = $this->postJson('/api/orders', [
            'products' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'status' => 201,
                'message' => 'Order placed successfully.',
                'data' => [
                    'id' => $response->json('data.id'),
                    'created_at' => $response->json('data.created_at'),
                    'products' => [[
                        'id' => $product->id,
                        'name' => $product->name,
                        'qty' => 1,
                    ]],
                ],
            ]);

        Notification::assertSentOnDemand(LowStockNotification::class);

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'low_stock_notified' => true,
        ]);
    }

    public function testMultipleProductsOrderSuccess()
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $ingredient1 = Ingredient::factory()->create(['stock_level' => 500]);
        $ingredient2 = Ingredient::factory()->create(['stock_level' => 1000]);

        $product1->ingredients()->attach($ingredient1->id, ['amount' => 50]);
        $product2->ingredients()->attach($ingredient2->id, ['amount' => 200]);

        $response = $this->postJson('/api/orders', [
            'products' => [
                ['product_id' => $product1->id, 'qty' => 1],
                ['product_id' => $product2->id, 'qty' => 2],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'status' => 201,
                'message' => 'Order placed successfully.',
                'data' => [
                    'id' => $response->json('data.id'),
                    'created_at' => $response->json('data.created_at'),
                    'products' => [
                        [
                            'id' => $product1->id,
                            'name' => $product1->name,
                            'qty' => 1,
                        ],
                        [
                            'id' => $product2->id,
                            'name' => $product2->name,
                            'qty' => 2,
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('data.id'),
        ]);

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient1->id,
            'stock_level' => 450,
        ]);

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient2->id,
            'stock_level' => 600,
        ]);
    }
}
