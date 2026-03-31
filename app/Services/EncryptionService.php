<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    protected string $key;

    public function __construct()
    {
        $this->key = config('app.encryption_key') ?? env('ENCRYPTION_KEY');
        if (empty($this->key)) {
            throw new \RuntimeException('Encryption key not configured');
        }
    }

    public function encrypt(string $data): string
    {
        return openssl_encrypt(
            $data,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->getIv()
        );
    }

    public function decrypt(string $encryptedData): ?string
    {
        $result = openssl_decrypt(
            $encryptedData,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->getIv()
        );
        return $result !== false ? $result : null;
    }

    protected function getIv(): string
    {
        // In production, store IV alongside encrypted data
        return substr(hash('sha256', $this->key), 0, 16);
    }

    public function hash(string $data): string
    {
        return hash('sha256', $data . $this->key);
    }
}
