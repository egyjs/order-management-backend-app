<?php

namespace Tests\Unit\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Ingredient;
use App\Models\Product;
use App\Notifications\LowStockNotification;
use App\Services\IngredientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class IngredientServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IngredientService $ingredientService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ingredientService = $this->app->make(IngredientService::class);
    }

    public function testHasSufficientStockReturnsTrueWhenStockIsEnough()
    {
        // Arrange
        $ingredient = Ingredient::factory()->create(['stock_level' => 500]);
        $requiredAmount = 100;

        // Act
        $hasSufficientStock = $this->ingredientService->hasSufficientStock($ingredient, $requiredAmount);

        // Assert
        $this->assertTrue($hasSufficientStock, 'Expected stock to be sufficient.');
    }

    public function testHasSufficientStockReturnsFalseWhenStockIsInsufficient()
    {
        // Arrange
        $ingredient = Ingredient::factory()->create(['stock_level' => 50]);
        $requiredAmount = 100;

        // Act
        $hasSufficientStock = $this->ingredientService->hasSufficientStock($ingredient, $requiredAmount);

        // Assert
        $this->assertFalse($hasSufficientStock, 'Expected stock to be insufficient.');
    }

    public function testHasStockLowerThanHalfReturnsTrueWhenStockIsBelowHalfMinimum()
    {
        // Arrange
        $ingredient = Ingredient::factory()->create(['stock_level' => 50, 'min_stock_level' => 200]);

        // Act
        $hasLowStock = $this->ingredientService->hasStockLowerThanHalf($ingredient);

        // Assert
        $this->assertTrue($hasLowStock, 'Expected stock to be below half the minimum.');
    }

    public function testHasStockLowerThanHalfReturnsFalseWhenStockIsAboveHalfMinimum()
    {
        // Arrange
        $ingredient = Ingredient::factory()->create(['stock_level' => 120, 'min_stock_level' => 200]);

        // Act
        $hasLowStock = $this->ingredientService->hasStockLowerThanHalf($ingredient);

        // Assert
        $this->assertFalse($hasLowStock, 'Expected stock to be above half the minimum.');
    }

    public function testNotifyLowStockSendsNotificationWhenStockIsLow()
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
        $this->assertTrue($ingredient->low_stock_notified, 'Expected the ingredient to be marked as notified.');
    }

    public function testNotifyLowStockDoesNotSendNotificationIfAlreadyNotified()
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

    public function testUpdateIngredientsStockUpdatesStockLevels()
    {
        // Arrange
        $product = Product::factory()->create();
        $ingredient = Ingredient::factory()->create(['stock_level' => 300]);
        $product->ingredients()->attach($ingredient->id, ['amount' => 100]); // Attach with pivot data

        $ingredients = $product->ingredients()->get(); // Now it has the pivot data

        // Act
        $this->ingredientService->updateIngredientsStock($ingredients, 2); // Requires 200

        // Assert
        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'stock_level' => 100,
        ]);
    }

    public function testUpdateIngredientsStockThrowsInsufficientStockException()
    {
        // Arrange
        $product = Product::factory()->create();
        $ingredient = Ingredient::factory()->create(['stock_level' => 100]);
        $product->ingredients()->attach($ingredient->id, ['amount' => 150]); // Attach with pivot data

        $ingredients = $product->ingredients()->get(); // Now it has the pivot data

        // Assert
        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage($ingredient->name);

        // Act
        $this->ingredientService->updateIngredientsStock($ingredients, 1); // Requires 150
    }

    public function testUpdateIngredientsStockSendsLowStockNotification()
    {
        // Arrange
        Notification::fake();
        $product = Product::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'stock_level' => 300, // Ensure enough stock to avoid exception
            'min_stock_level' => 600, // Low stock threshold
        ]);
        $product->ingredients()->attach($ingredient->id, ['amount' => 150]); // Attach with pivot data
        $ingredients = $product->ingredients()->get(); // Now it has the pivot data

        // Act
        $this->ingredientService->updateIngredientsStock($ingredients, 1); // Requires 150

        // Assert
        Notification::assertSentOnDemand(LowStockNotification::class);
        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'stock_level' => 150, // Expected stock level after decrement (300 - 150)
        ]);
    }
}
