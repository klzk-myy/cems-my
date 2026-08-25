<?php

namespace App\Services\System;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentStorageService
{
    protected const BASE = 'documents';

    public function exists(string $path): bool
    {
        return Storage::exists($this->validatePath($path));
    }

    public function delete(string $path): void
    {
        Storage::delete($this->validatePath($path));
    }

    public function download(string $path): BinaryFileResponse|StreamedResponse
    {
        return Storage::download($this->validatePath($path));
    }

    public function path(string $path): string
    {
        return Storage::path($this->validatePath($path));
    }

    protected function validatePath(string $path): string
    {
        $path = str_replace(chr(0), '', $path);

        if (str_starts_with($path, '/') || str_contains($path, '..')) {
            throw new \InvalidArgumentException("Invalid document path: {$path}");
        }

        $filename = basename($path);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new \InvalidArgumentException("Invalid document path: {$path}");
        }

        return self::BASE.'/'.$path;
    }
}
