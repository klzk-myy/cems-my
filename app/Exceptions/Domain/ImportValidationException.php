<?php

namespace App\Exceptions\Domain;

use Exception;

class ImportValidationException extends Exception
{
    public function __construct(string $message = 'Import validation failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
