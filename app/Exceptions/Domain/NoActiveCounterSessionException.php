<?php

namespace App\Exceptions\Domain;

class NoActiveCounterSessionException extends DomainException
{
    public function __construct(public int $counterId, ?string $sessionDate = null)
    {
        $date = $sessionDate ? " on date {$sessionDate}" : '';
        parent::__construct("No active session found for counter ID: {$counterId}{$date}.");
    }

    public function getStatusCode(): int
    {
        return 409;
    }
}
