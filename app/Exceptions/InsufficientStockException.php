<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct($ingredientName)
    {
        parent::__construct("Not enough stock for ingredient: $ingredientName");
    }
}
