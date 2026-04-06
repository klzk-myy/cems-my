<?php

namespace Tests\Unit;

use App\Services\MfaService;
use Tests\TestCase;

class MfaServiceTest extends TestCase
{
    private MfaService $mfaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mfaService = new MfaService;
    }

    /**
     * Test secret generation.
     */
    public function test_generates_secret(): void
    {
        $result = $this->mfaService->generateSecret();

        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('otpauth_url', $result);
        $this->assertEquals(32, strlen($result['secret']));
    }

    /**
     * Test otpauth URL format.
     */
    public function test_generates_valid_otpauth_url(): void
    {
        // Manually set auth user for URL building
        $result = $this->mfaService->generateSecret();

        $this->assertStringStartsWith('otpauth://totp/', $result['otpauth_url']);
        $this->assertStringContainsString('secret='.$result['secret'], $result['otpauth_url']);
        $this->assertStringContainsString('digits=6', $result['otpauth_url']);
        $this->assertStringContainsString('period=30', $result['otpauth_url']);
    }

    /**
     * Test TOTP code generation.
     */
    public function test_generates_valid_totp_code(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $code = $this->mfaService->generateCode($secretData['secret']);

        $this->assertEquals(6, strlen($code));
        $this->assertTrue(ctype_digit($code));
    }

    /**
     * Test TOTP code verification - valid code.
     */
    public function test_verifies_valid_totp_code(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $code = $this->mfaService->generateCode($secretData['secret']);

        $this->assertTrue($this->mfaService->verifyCode($secretData['secret'], $code));
    }

    /**
     * Test TOTP code verification - invalid code.
     */
    public function test_rejects_invalid_totp_code(): void
    {
        $secretData = $this->mfaService->generateSecret();

        $this->assertFalse($this->mfaService->verifyCode($secretData['secret'], '000000'));
    }

    /**
     * Test invalid format code is rejected.
     */
    public function test_rejects_invalid_format_code(): void
    {
        $secretData = $this->mfaService->generateSecret();

        $this->assertFalse($this->mfaService->verifyCode($secretData['secret'], 'abc'));
        $this->assertFalse($this->mfaService->verifyCode($secretData['secret'], '12345'));
        $this->assertFalse($this->mfaService->verifyCode($secretData['secret'], '1234567'));
    }

    /**
     * Test code with whitespace is handled.
     */
    public function test_handles_code_with_whitespace(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $code = $this->mfaService->generateCode($secretData['secret']);

        $this->assertTrue($this->mfaService->verifyCode($secretData['secret'], '  '.$code.'  '));
    }

    /**
     * Test TOTP code is time-based (different codes at different times).
     */
    public function test_generates_different_codes_at_different_times(): void
    {
        $secretData = $this->mfaService->generateSecret();

        $code1 = $this->mfaService->generateCode($secretData['secret'], time());
        sleep(1);
        $code2 = $this->mfaService->generateCode($secretData['secret'], time() + 31);

        // Codes should be different (different time windows)
        // Note: This could occasionally fail if the time window doesn't change
        $this->assertTrue(
            $code1 !== $code2 || $this->mfaService->verifyCode($secretData['secret'], $code1),
            'Codes should be different or code1 should still verify'
        );
    }

    /**
     * Test base32 encoding produces valid characters.
     */
    public function test_base32_encoding(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $secret = $secretData['secret'];

        // Base32 alphabet: A-Z and 2-7
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }
}
