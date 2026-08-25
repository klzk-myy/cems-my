<?php

namespace App\Http\Traits;

use App\Exceptions\Domain\InvalidCurrencyException;
use App\Exceptions\Domain\InvalidIpAddressException;
use App\Services\Security\IpValidationService;

trait ValidatorMethods
{
    protected ?IpValidationService $validatorIpService = null;

    protected function resolveIpValidationService(): IpValidationService
    {
        return $this->validatorIpService ??= app(IpValidationService::class);
    }

    protected function validateCurrencyCode(string $currencyCode): void
    {
        if (! preg_match('/^[A-Z]{3}$/', $currencyCode)) {
            throw new InvalidCurrencyException($currencyCode);
        }
    }

    protected function validateIpAddress(?string $ipAddress): void
    {
        if ($ipAddress && ! $this->resolveIpValidationService()->isValidIp($ipAddress)) {
            throw new InvalidIpAddressException($ipAddress);
        }
    }

    protected function validateXml(string $content): bool
    {
        libxml_use_internal_errors(true);
        $result = simplexml_load_string($content);

        return $result !== false;
    }

    protected function validateJson(string $content): bool
    {
        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function validateCsv(string $content): bool
    {
        $lines = explode("\n", $content);
        if (count($lines) < 2) {
            return false;
        }
        $firstLine = $lines[0];

        return str_contains($firstLine, ',') || str_contains($firstLine, "\t");
    }
}
