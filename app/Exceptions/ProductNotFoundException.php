<?php

namespace App\Exceptions;

use Exception;

class ProductNotFoundException extends Exception
{
    public function __construct($productId)
    {
        parent::__construct("Product not found: $productId");
    }
}
