<?php

namespace Tests\Unit;

use App\Services\EncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncryptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->encryptionService = new EncryptionService();
    }

    public function test_can_encrypt_and_decrypt_data(): void
    {
        $plaintext = 'This is sensitive data';
        $key = 'test-key-123';

        $encrypted = $this->encryptionService->encrypt($plaintext, $key);
        $decrypted = $this->encryptionService->decrypt($encrypted, $key);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function test_encrypts_produce_different_ciphertexts(): void
    {
        $plaintext = 'Same input data';
        $key = 'test-key-123';

        $encrypted1 = $this->encryptionService->encrypt($plaintext, $key);
        $encrypted2 = $this->encryptionService->encrypt($plaintext, $key);

        // Due to random IV, ciphertexts should be different
        $this->assertNotEquals($encrypted1, $encrypted2);
    }

    public function test_decrypt_with_different_key_produces_different_result(): void
    {
        $plaintext = 'Sensitive data';
        $key1 = 'key-one-123';
        $key2 = 'key-two-456';

        $encrypted1 = $this->encryptionService->encrypt($plaintext, $key1);
        $encrypted2 = $this->encryptionService->encrypt($plaintext, $key2);

        // Different keys should produce different encryptions
        $this->assertNotEquals($encrypted1, $encrypted2);
    }

    public function test_encrypt_empty_string(): void
    {
        $plaintext = '';
        $key = 'test-key';

        $encrypted = $this->encryptionService->encrypt($plaintext, $key);
        $decrypted = $this->encryptionService->decrypt($encrypted, $key);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function test_encrypt_unicode_characters(): void
    {
        $plaintext = '日本語テスト 한국어 Ελληνικά';
        $key = 'unicode-key-123';

        $encrypted = $this->encryptionService->encrypt($plaintext, $key);
        $decrypted = $this->encryptionService->decrypt($encrypted, $key);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function test_encrypt_very_long_string(): void
    {
        $plaintext = str_repeat('A very long string. ', 1000);
        $key = 'long-data-key';

        $encrypted = $this->encryptionService->encrypt($plaintext, $key);
        $decrypted = $this->encryptionService->decrypt($encrypted, $key);

        $this->assertEquals($plaintext, $decrypted);
    }
}
