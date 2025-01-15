<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderProcessingException;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

/**
 * Controller class responsible for handling order-related HTTP requests.
 */
class OrderController extends Controller
{
    /**
     * OrderController constructor.
     *
     * @param  OrderService  $orderService  The service responsible for order processing.
     */
    public function __construct(protected OrderService $orderService) {}

    /**
     * Handles the creation of a new order.
     *
     * @param  StoreOrderRequest  $request  The request object containing order data.
     * @return JsonResponse The JSON response indicating success or failure.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            // Validate and process the order
            $validatedData = $request->validated();
            $order = $this->orderService->process($validatedData);
            $order->load('products');

            // Success response with status code 201 (Created)
            return Response::success('Order placed successfully.', new OrderResource($order), 201);
        } catch (OrderProcessingException $e) {
            // Log the exception for further debugging
            report($e);

            return Response::errors($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Catch unexpected errors
            report($e);

            return Response::errors(
                'An unexpected error occurred. Please try again later.',
                500
            );
        }
    }
}
