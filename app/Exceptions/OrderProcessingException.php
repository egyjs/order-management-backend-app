<?php

namespace App\Exceptions;

use Exception;

class OrderProcessingException extends Exception
{
    public function __construct($message)
    {
        parent::__construct("Order processing failed: $message");
    }
}
