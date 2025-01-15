<?php

namespace Tests\Unit\Services;

use App\Exceptions\OrderProcessingException;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = $this->app->make(OrderService::class);
    }

    public function testProcessOrderSuccessfully()
    {
        // Arrange
        $product = Product::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'stock_level' => 20000,
            'min_stock_level' => 20000,
        ]);

        $product->ingredients()->attach($ingredient->id, ['amount' => 150]);

        $orderData = [
            'products' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ];

        // Act
        $order = $this->orderService->process($orderData);

        // Assert
        $this->assertInstanceOf(Order::class, $order, 'The returned object should be an instance of Order.');
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('product_ingredients', [
            'ingredient_id' => $ingredient->id,
            'product_id' => $product->id,
            'amount' => 150,
        ]);
        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'stock_level' => 19850,
        ]);
    }

    public function testProcessOrderThrowsProductNotFoundException()
    {
        // Arrange
        $orderData = [
            'products' => [
                ['product_id' => 'non-existing-id', 'qty' => 1],
            ],
        ];

        // Assert
        $this->expectException(OrderProcessingException::class);
        $this->expectExceptionMessageMatches('/Product not found/');

        // Act
        $this->orderService->process($orderData);
    }

    public function testProcessOrderThrowsInsufficientStockException()
    {
        // Arrange
        $product = Product::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'stock_level' => 100,
            'min_stock_level' => 100,
        ]);

        $product->ingredients()->attach($ingredient->id, ['amount' => 150]);

        $orderData = [
            'products' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ];

        // Assert
        $this->expectException(OrderProcessingException::class);
        $this->expectExceptionMessageMatches('/Not enough stock/');

        // Act
        $this->orderService->process($orderData);
    }

    public function testProcessOrderWithMultipleProducts()
    {
        // Arrange
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $ingredient1 = Ingredient::factory()->create(['stock_level' => 300]);
        $ingredient2 = Ingredient::factory()->create(['stock_level' => 500]);

        $product1->ingredients()->attach($ingredient1->id, ['amount' => 100]);
        $product2->ingredients()->attach($ingredient2->id, ['amount' => 200]);

        $orderData = [
            'products' => [
                ['product_id' => $product1->id, 'qty' => 2], // 2 x 100 = 200
                ['product_id' => $product2->id, 'qty' => 1], // 1 x 200 = 200
            ],
        ];

        // Act
        $order = $this->orderService->process($orderData);

        // Assert
        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('ingredients', ['id' => $ingredient1->id, 'stock_level' => 100]);
        $this->assertDatabaseHas('ingredients', ['id' => $ingredient2->id, 'stock_level' => 300]);
    }
}
