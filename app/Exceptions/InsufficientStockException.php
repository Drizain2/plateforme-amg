<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
     public function __construct(
        string $message,
        public readonly int $available = 0,
        public readonly int $requested = 0,
    ) {
        parent::__construct($message);
    }
}
