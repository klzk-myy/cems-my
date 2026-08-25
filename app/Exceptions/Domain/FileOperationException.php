<?php

namespace App\Exceptions\Domain;

use Exception;

class FileOperationException extends Exception
{
    public function __construct(string $message = 'File operation failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
