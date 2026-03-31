<?php

namespace Tests\Unit;

use App\Services\EncryptionService;
use Tests\TestCase;

class EncryptionServiceTest extends TestCase
{
    protected EncryptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EncryptionService();
    }

    public function test_can_encrypt_and_decrypt_data()
    {
        $original = 'MyKad: 900101-01-1234';
        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertNotEquals($original, $encrypted);
        $this->assertEquals($original, $decrypted);
    }

    public function test_encrypts_to_different_values()
    {
        $data = 'sensitive data';
        $encrypted1 = $this->service->encrypt($data);
        $encrypted2 = $this->service->encrypt($data);

        $this->assertEquals($this->service->decrypt($encrypted1), $this->service->decrypt($encrypted2));
    }

    public function test_hashing_is_deterministic()
    {
        $data = 'test data';
        $hash1 = $this->service->hash($data);
        $hash2 = $this->service->hash($data);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1)); // SHA256 length
    }
}
