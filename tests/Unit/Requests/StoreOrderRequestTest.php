<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreOrderRequestTest extends TestCase
{
    protected StoreOrderRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreOrderRequest(); // Common request instance for all tests
    }

    public function testValidationPassesWithValidData()
    {
        // Arrange
        $product = Product::factory()->create();
        $data = [
            'products' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes(), 'Validation should pass with valid data.');
    }

    public function testValidationFailsWithInvalidData()
    {
        // Arrange
        $data = [
            'products' => [
                ['product_id' => null, 'qty' => 0], // Invalid product ID and quantity
            ],
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->passes(), 'Validation should fail with invalid data.');
    }

    public function testValidationFailsWhenProductsAreEmpty()
    {
        // Arrange
        $data = [
            'products' => [], // No products provided
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->passes(), 'Validation should fail when products array is empty.');
    }

    public function testValidationFailsWithoutProductsKey()
    {
        // Arrange
        $data = []; // Missing "products" key

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertFalse($validator->passes(), 'Validation should fail when products key is missing.');
    }
}
