<?php

namespace App\Services;

class EncryptionService
{
    protected string $key;

    public function __construct()
    {
        $rawKey = config('app.encryption_key') ?? env('ENCRYPTION_KEY');
        if (empty($rawKey)) {
            throw new \RuntimeException('Encryption key not configured');
        }
        // Derive a proper 32-byte key using SHA-256 (AES-256-CBC requires 32 bytes)
        $this->key = hash('sha256', $rawKey, true);
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

    /**
     * Hash data using HMAC-SHA256 to prevent length extension attacks.
     *
     * @param  string  $data  Data to hash
     * @return string  HMAC-SHA256 hash as hex string
     */
    public function hash(string $data): string
    {
        return hash_hmac('sha256', $data, $this->key);
    }
}
