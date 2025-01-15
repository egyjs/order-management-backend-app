<?php

namespace Tests\Unit\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testItCreatesAnOrderSuccessfully()
    {
        // Mock the OrderService
        $orderServiceMock = Mockery::mock(OrderService::class);
        $orderServiceMock
            ->shouldReceive('process')
            ->once()
            ->andReturn(Order::factory()->create());

        // Bind the mock to the container
        $this->app->instance(OrderService::class, $orderServiceMock);

        $product = Product::first();

        $requestData = [
            'products' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ];

        // Simulate the POST request
        $response = $this->postJson('/api/orders', $requestData);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'message',
            'status',
            'data',
        ]);
    }

    public function testItFailsToCreateOrderDueToException()
    {
        // Mock the OrderService to throw an exception
        $orderServiceMock = Mockery::mock(OrderService::class);
        $orderServiceMock
            ->shouldReceive('processOrder')
            ->andThrow(new Exception('Order processing failed'));

        // Bind the mock to the container
        $this->app->instance(OrderService::class, $orderServiceMock);

        $requestData = [
            'products' => [
                ['product_id' => 'INVALID-ID', 'qty' => 1],
            ],
        ];

        // Simulate the POST request
        $response = $this->postJson('/api/orders', $requestData);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'products.0.product_id',
            ],
        ]);
    }
}
