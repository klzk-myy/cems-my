<?php

namespace App\Exceptions\Domain;

use Exception;

class CurrencyNotFoundException extends Exception
{
    public function __construct(string $currencyCode, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Currency {$currencyCode} not found", $code, $previous);
    }
}
