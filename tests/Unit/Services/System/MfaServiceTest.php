<?php

namespace Tests\Unit\Services\System;

use App\Models\User;
use App\Services\System\MfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MfaServiceTest extends TestCase
{
    use RefreshDatabase;

    private MfaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MfaService::class);
    }

    #[Test]
    public function generate_secret_returns_secret_and_otpauth_url(): void
    {
        $result = $this->service->generateSecret('user@example.com');

        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('otpauth_url', $result);
        $this->assertStringStartsWith('otpauth://totp/', $result['otpauth_url']);
        $this->assertNotEmpty($result['secret']);
    }

    #[Test]
    public function verify_code_returns_true_for_code_generated_from_secret(): void
    {
        $secret = $this->service->generateSecret()['secret'];
        $code = $this->service->generateCode($secret);

        $this->assertTrue($this->service->verifyCode($secret, $code));
    }

    #[Test]
    public function verify_code_returns_false_for_wrong_code(): void
    {
        $secret = $this->service->generateSecret()['secret'];

        $this->assertFalse($this->service->verifyCode($secret, '000000'));
    }

    #[Test]
    public function generate_recovery_codes_returns_unique_codes(): void
    {
        $user = User::factory()->create();

        $codes = $this->service->generateRecoveryCodes($user);

        $this->assertCount(10, $codes);
        $this->assertEquals(count($codes), count(array_unique($codes)));
    }

    #[Test]
    public function verify_recovery_code_returns_true_for_generated_code(): void
    {
        $user = User::factory()->create();
        $codes = $this->service->generateRecoveryCodes($user);

        $this->assertTrue($this->service->verifyRecoveryCode($user, $codes[0]));
    }

    #[Test]
    public function verify_recovery_code_returns_false_for_invalid_code(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->service->verifyRecoveryCode($user, 'AAAA-BBBB'));
    }

    #[Test]
    public function recovery_code_is_single_use(): void
    {
        $user = User::factory()->create();
        $codes = $this->service->generateRecoveryCodes($user);

        // First use succeeds...
        $this->assertTrue($this->service->verifyRecoveryCode($user, $codes[0]));

        // ...second use of the SAME code must fail because it was marked used.
        $this->assertFalse(
            $this->service->verifyRecoveryCode($user, $codes[0]),
            'A consumed recovery code must be rejected on second use'
        );

        // Remaining pool shrank by exactly one.
        $this->assertEquals(9, $this->service->getRemainingRecoveryCodesCount($user));
    }

    #[Test]
    public function unused_recovery_codes_are_not_affected_by_another_codes_use(): void
    {
        $user = User::factory()->create();
        $codes = $this->service->generateRecoveryCodes($user);

        $this->assertTrue($this->service->verifyRecoveryCode($user, $codes[3]));

        // A different, still-unused code keeps working afterwards.
        $this->assertTrue($this->service->verifyRecoveryCode($user, $codes[7]));
        $this->assertEquals(8, $this->service->getRemainingRecoveryCodesCount($user));
    }
}
