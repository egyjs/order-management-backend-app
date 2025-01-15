<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderProcessingException;
use App\Exceptions\ProductNotFoundException;
use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Service class responsible for handling order processing.
 */
class OrderService
{
    /**
     * @var Order The order instance being processed.
     */
    protected Order $order;

    /**
     * OrderService constructor.
     *
     * @param  IngredientService  $ingredientService  The service responsible for managing ingredient stock.
     */
    public function __construct(protected IngredientService $ingredientService) {}

    /**
     * Processes an order.
     *
     * @param  array  $orderData  The data of the order to be processed.
     * @return Order The processed order.
     *
     * @throws OrderProcessingException If an error occurs during order processing.
     */
    public function process(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {
            try {
                // Create a new order record using a factory helper for cleaner creation
                $this->order = Order::create();

                // Delegate the product addition to a dedicated method
                foreach ($orderData['products'] as $productOrder) {
                    $this->linkProduct($productOrder);
                }

                return $this->order;
            } catch (ProductNotFoundException|InsufficientStockException $exception) {
                throw new OrderProcessingException($exception->getMessage());
            } catch (Exception $exception) {
                report($exception);  // Use Laravel's `report` helper to log exceptions
                throw new OrderProcessingException('An error occurred while processing the order.');
            }
        });
    }

    /**
     * Links a product to the order and updates ingredient stock.
     *
     * @param  array  $productDetails  The details of the product to be linked.
     *
     * @throws ProductNotFoundException If the product is not found.
     * @throws InsufficientStockException If there is insufficient stock of ingredients.
     */
    private function linkProduct(array $productDetails): void
    {
        $productId = Arr::get($productDetails, 'product_id');
        $qty = (int) Arr::get($productDetails, 'qty', 1);

        $product = Product::with('ingredients')->find($productId);

        if (! $product) {
            throw new ProductNotFoundException("Product with ID $productId not found.");
        }

        $this->order->products()->attach($productId, ['qty' => $qty]);

        // Use SRP: Delegate ingredient stock check to a dedicated method
        $this->ingredientService->updateIngredientsStock($product->ingredients, $qty);
    }
}
