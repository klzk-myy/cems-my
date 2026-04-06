<?php

namespace App\Services;

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
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt(
            $data,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv.$ciphertext);
    }

    public function decrypt(string $encryptedData): ?string
    {
        $data = base64_decode($encryptedData);
        if ($data === false || strlen($data) < 17) {
            return null;
        }
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        $result = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
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
        return hash('sha256', $data.$this->key);
    }
}
