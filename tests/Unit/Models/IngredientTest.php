<?php

namespace Tests\Unit\Models;

use App\Models\Ingredient;
use App\Models\Product;
use App\Notifications\LowStockNotification;
use App\Services\IngredientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class IngredientTest extends TestCase
{
    use RefreshDatabase;

    protected IngredientService $ingredientService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ingredientService = $this->app->make(IngredientService::class);
    }

    public function testIngredientHasProductRelationship()
    {
        // Arrange
        $ingredient = Ingredient::factory()->create();
        $product = Product::factory()->create();

        // Act
        $ingredient->products()->attach($product->id, ['amount' => 100]);

        // Assert
        $this->assertInstanceOf(Product::class, $ingredient->products->first());
        $this->assertEquals(100, $ingredient->products->first()->pivot->amount);
    }

    public function testItSendsLowStockNotification()
    {
        // Arrange
        Notification::fake();
        $ingredient = Ingredient::factory()->create([
            'stock_level' => 50,
            'min_stock_level' => 200,
            'low_stock_notified' => false,
        ]);

        // Act
        $this->ingredientService->notifyLowStock($ingredient);

        // Assert
        Notification::assertSentOnDemand(LowStockNotification::class);
        $ingredient->refresh();
        $this->assertTrue($ingredient->low_stock_notified);
    }

    public function testLowStockNotificationNotSentIfAlreadyNotified()
    {
        // Arrange
        Notification::fake();
        $ingredient = Ingredient::factory()->create([
            'stock_level' => 50,
            'min_stock_level' => 200,
            'low_stock_notified' => true, // Already notified
        ]);

        // Act
        $this->ingredientService->notifyLowStock($ingredient);

        // Assert
        Notification::assertNothingSent();
    }
}
