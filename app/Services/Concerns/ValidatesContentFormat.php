<?php

namespace App\Services\Concerns;

trait ValidatesContentFormat
{
    /**
     * Validate that the given content is well-formed XML.
     */
    protected function validateXml(string $content): bool
    {
        libxml_use_internal_errors(true);
        $result = simplexml_load_string($content);

        return $result !== false;
    }

    /**
     * Validate that the given content is well-formed JSON.
     */
    protected function validateJson(string $content): bool
    {
        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate that the given content is a CSV-like table.
     */
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
