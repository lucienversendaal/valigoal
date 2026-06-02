<?php

namespace App\Exceptions;

use RuntimeException;

class PredictionLockedException extends RuntimeException
{
    public function __construct(string $message = 'Deze wedstrijd is gesloten; voorspellen kan niet meer.')
    {
        parent::__construct($message);
    }
}
