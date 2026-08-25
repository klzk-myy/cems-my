<?php

namespace App\Exceptions\Domain;

class SanctionsImportException extends DomainException
{
    public function __construct(string $message, ?string $filePath = null)
    {
        $suffix = $filePath ? " (file: {$filePath})" : '';
        parent::__construct($message.$suffix);
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
