<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Ingredient;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Service class responsible for managing ingredient stock.
 */
class IngredientService
{
    /**
     * Checks if there is sufficient stock of an ingredient.
     *
     * @param  Ingredient  $ingredient  The ingredient to check.
     * @param  int  $amount  The required amount of the ingredient.
     * @return bool True if there is sufficient stock, false otherwise.
     */
    public function hasSufficientStock(Ingredient $ingredient, int $amount): bool
    {
        return $ingredient->stock_level >= $amount;
    }

    /**
     * Checks if the stock level of an ingredient is less than half of the minimum stock level.
     *
     * @param  Ingredient  $ingredient  The ingredient to check.
     * @return bool True if the stock level is less than half of the minimum, false otherwise.
     */
    public function hasStockLowerThanHalf(Ingredient $ingredient): bool
    {
        return $ingredient->stock_level < $ingredient->min_stock_level / 2;
    }

    /**
     * Sends a notification if the stock level of an ingredient is low.
     *
     * @param  Ingredient  $ingredient  The ingredient to check and notify.
     */
    public function notifyLowStock(Ingredient $ingredient): void
    {
        if (! $ingredient->low_stock_notified && $this->hasStockLowerThanHalf($ingredient)) {

            Notification::route('mail', config('mail.merchant_email'))
                ->notify(new LowStockNotification($ingredient));

            $ingredient->low_stock_notified = true;
            $ingredient->save();
        }
    }

    /**
     * Updates the stock levels of ingredients based on the qty required.
     *
     * @param  array<Ingredient>|Collection<Ingredient>  $ingredients  The list of ingredients to update.
     * @param  int  $qty  The qty of the product being processed.
     *
     * @throws InsufficientStockException If there is insufficient stock of any ingredient.
     */
    public function updateIngredientsStock(array|Collection $ingredients, int $qty): void
    {
        foreach ($ingredients as $ingredient) {
            $requiredAmount = $ingredient->pivot->amount * $qty; // ProductIngredient::amount * requested qty

            if (! $this->hasSufficientStock($ingredient, $requiredAmount)) {
                throw new InsufficientStockException($ingredient->name);
            }

            $ingredient->decrement('stock_level', $requiredAmount);

            if ($this->hasStockLowerThanHalf($ingredient)) {
                $this->notifyLowStock($ingredient);
            }
        }
    }
}
