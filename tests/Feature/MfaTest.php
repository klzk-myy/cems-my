<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MfaTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $tellerUser;

    protected MfaService $mfaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mfaService = new MfaService;

        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@123456'),
            'role' => 'admin',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => 'teller',
            'mfa_enabled' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Test MFA setup page is accessible for admin.
     */
    public function test_mfa_setup_page_is_accessible_for_admin(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/mfa/setup');

        $response->assertStatus(200);
        $response->assertSee('Set Up Multi-Factor Authentication');
    }

    /**
     * Test MFA setup generates secret and otpauth URL.
     */
    public function test_mfa_setup_generates_secret_and_url(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/mfa/setup');

        $response->assertStatus(200);
        $response->assertViewHas('secret');
        $response->assertViewHas('otpauthUrl');

        $secret = $response->viewData('secret');
        $otpauthUrl = $response->viewData('otpauthUrl');

        $this->assertNotEmpty($secret);
        $this->assertEquals(32, strlen($secret)); // Base32 encoded 20 bytes = 32 chars
        $this->assertStringContainsString('otpauth://totp/', $otpauthUrl);
        $this->assertStringContainsString($secret, $otpauthUrl);
    }

    /**
     * Test MFA verification page redirects when MFA not enabled.
     */
    public function test_mfa_verify_redirects_when_mfa_not_enabled(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/mfa/verify');

        $response->assertRedirect('/mfa/setup');
    }

    /**
     * Test MFA verification page loads when MFA is enabled.
     */
    public function test_mfa_verify_page_loads_when_enabled(): void
    {
        // Enable MFA for user
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->enableMfa($this->adminUser);

        $response = $this->actingAs($this->adminUser)->get('/mfa/verify');

        $response->assertStatus(200);
        $response->assertSee('Verify Your Identity');
    }

    /**
     * Test MFA setup verifies valid code.
     */
    public function test_mfa_setup_verifies_valid_code(): void
    {
        // Get a valid code first
        $secretData = $this->mfaService->generateSecret();
        $code = $this->mfaService->generateCode($secretData['secret']);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['mfa_pending_secret' => $secretData['secret']])
            ->post('/mfa/setup', ['code' => $code]);

        // Controller shows recovery codes view after successful setup
        $response->assertOk();
        $response->assertViewIs('mfa.recovery-codes');

        // Verify MFA is enabled
        $this->adminUser->refresh();
        $this->assertTrue($this->adminUser->mfa_enabled);
        $this->assertNotEmpty($this->adminUser->mfa_secret);
    }

    /**
     * Test MFA setup rejects invalid code.
     */
    public function test_mfa_setup_rejects_invalid_code(): void
    {
        $secretData = $this->mfaService->generateSecret();

        $response = $this->actingAs($this->adminUser)
            ->withSession(['mfa_pending_secret' => $secretData['secret']])
            ->post('/mfa/setup', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
    }

    /**
     * Test MFA verification with valid code.
     */
    public function test_mfa_verify_with_valid_code(): void
    {
        // Enable MFA
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->enableMfa($this->adminUser);

        // Generate valid code
        $code = $this->mfaService->generateCode($secretData['secret']);

        $response = $this->actingAs($this->adminUser)
            ->post('/mfa/verify', ['code' => $code]);

        $response->assertRedirect('/dashboard');
        $this->assertTrue(session('mfa_verified'));
    }

    /**
     * Test MFA verification with recovery code.
     */
    public function test_mfa_verify_with_recovery_code(): void
    {
        // Enable MFA and generate recovery codes
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->enableMfa($this->adminUser);
        $recoveryCodes = $this->mfaService->generateRecoveryCodes($this->adminUser);
        $firstCode = $recoveryCodes[0];

        // Try with recovery code
        $response = $this->actingAs($this->adminUser)
            ->withSession([])
            ->post('/mfa/verify', ['code' => $firstCode]);

        $response->assertRedirect();
        $this->assertTrue(session('mfa_verified'));

        // Recovery code should now be used
        $this->assertEquals(9, $this->mfaService->getRemainingRecoveryCodesCount($this->adminUser));
    }

    /**
     * Test MFA verification rejects invalid code.
     */
    public function test_mfa_verify_rejects_invalid_code(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->enableMfa($this->adminUser);

        $response = $this->actingAs($this->adminUser)
            ->post('/mfa/verify', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
    }

    /**
     * Test MFA verification rejects reused recovery code.
     */
    public function test_mfa_verify_rejects_reused_recovery_code(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->enableMfa($this->adminUser);
        $recoveryCodes = $this->mfaService->generateRecoveryCodes($this->adminUser);
        $firstCode = $recoveryCodes[0];

        // First use should work
        $this->actingAs($this->adminUser)
            ->post('/mfa/verify', ['code' => $firstCode]);

        // Second use should fail
        $response = $this->actingAs($this->adminUser)
            ->post('/mfa/verify', ['code' => $firstCode]);

        $response->assertSessionHasErrors('code');
    }

    /**
     * Test trusted device bypasses MFA verification.
     */
    public function test_trusted_device_bypasses_mfa(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->enableMfa($this->adminUser);

        // Create trusted device using same fingerprint generation as controller
        // This must match: sha256(userAgent + '|' + ip + '|' + acceptLanguage)
        $fingerprint = hash('sha256', implode('|', [
            'test-device',
            '127.0.0.1',
            'en',
        ]));
        $this->mfaService->rememberDevice($this->adminUser, $fingerprint, 'Test Device');

        // Access verify page with trusted device
        $response = $this->actingAs($this->adminUser)
            ->withServerVariables([
                'HTTP_USER_AGENT' => 'test-device',
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_ACCEPT_LANGUAGE' => 'en',
            ])
            ->get('/mfa/verify');

        // Should redirect to dashboard (MFA bypassed)
        $response->assertRedirect();
    }

    /**
     * Test MFA disable requires valid code.
     */
    public function test_mfa_disable_requires_valid_code(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->enableMfa($this->adminUser);

        $response = $this->actingAs($this->adminUser)
            ->post('/mfa/disable', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
    }

    /**
     * Test MFA disable with valid code.
     */
    public function test_mfa_disable_with_valid_code(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->enableMfa($this->adminUser);
        $code = $this->mfaService->generateCode($secretData['secret']);

        $response = $this->actingAs($this->adminUser)
            ->post('/mfa/disable', ['code' => $code]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('status', 'MFA has been disabled successfully.');

        $this->adminUser->refresh();
        $this->assertFalse($this->adminUser->mfa_enabled);
    }

    /**
     * Test transaction create requires MFA verification.
     */
    public function test_transaction_create_requires_mfa(): void
    {
        // Create manager user with MFA enabled
        $manager = User::create([
            'username' => 'manager',
            'email' => 'manager@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => 'manager',
            'mfa_enabled' => true,
            'mfa_secret' => Crypt::encryptString($this->mfaService->generateSecret()['secret']),
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->get('/transactions/create');

        // Should redirect to MFA verify
        $response->assertRedirect('/mfa/verify');
    }

    /**
     * Test transaction create allows access after MFA verification.
     */
    public function test_transaction_create_allows_after_mfa(): void
    {
        // Create manager user with MFA enabled
        $secretData = $this->mfaService->generateSecret();
        $manager = User::create([
            'username' => 'manager',
            'email' => 'manager@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => 'manager',
            'mfa_enabled' => true,
            'mfa_secret' => Crypt::encryptString($secretData['secret']),
            'is_active' => true,
        ]);

        // Verify MFA first
        $code = $this->mfaService->generateCode($secretData['secret']);
        $this->actingAs($manager)
            ->withSession(['mfa_verified' => true])
            ->post('/mfa/verify', ['code' => $code]);

        // Now access transaction create
        $response = $this->actingAs($manager)
            ->withSession(['mfa_verified' => true])
            ->get('/transactions/create');

        $response->assertStatus(200);
    }

    /**
     * Test Teller role does not require MFA setup.
     */
    public function test_teller_does_not_require_mfa_setup(): void
    {
        $response = $this->actingAs($this->tellerUser)->get('/mfa/setup');

        // Teller should be redirected somewhere (either setup or verify)
        // Since tellers are not in require_for_roles, they shouldn't be forced to setup
        $this->assertNotEquals(403, $response->status());
    }

    /**
     * Test MfaService generates valid TOTP codes.
     */
    public function test_mfa_service_generates_valid_totp(): void
    {
        $secretData = $this->mfaService->generateSecret();

        // Should be able to generate multiple codes
        $code1 = $this->mfaService->generateCode($secretData['secret']);
        sleep(1);
        $code2 = $this->mfaService->generateCode($secretData['secret']);

        $this->assertEquals(6, strlen($code1));
        $this->assertEquals(6, strlen($code2));
        $this->assertTrue($this->mfaService->verifyCode($secretData['secret'], $code1));
    }

    /**
     * Test MfaService stores and retrieves secret.
     */
    public function test_mfa_service_stores_and_retrieves_secret(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);

        $retrievedSecret = $this->mfaService->getSecret($this->adminUser);

        $this->assertEquals($secretData['secret'], $retrievedSecret);
    }

    /**
     * Test recovery codes are generated on MFA enable.
     */
    public function test_recovery_codes_generated_on_mfa_enable(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $recoveryCodes = $this->mfaService->generateRecoveryCodes($this->adminUser);
        $this->mfaService->enableMfa($this->adminUser);

        $this->assertCount(10, $recoveryCodes);
        $this->assertEquals(10, $this->mfaService->getRemainingRecoveryCodesCount($this->adminUser));
    }

    /**
     * Test audit log is created when MFA is enabled.
     */
    public function test_audit_log_created_when_mfa_enabled(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->generateRecoveryCodes($this->adminUser);
        $this->mfaService->enableMfa($this->adminUser);

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'mfa_enabled',
        ]);
    }

    /**
     * Test audit log is created when MFA is disabled.
     */
    public function test_audit_log_created_when_mfa_disabled(): void
    {
        $secretData = $this->mfaService->generateSecret();
        $this->mfaService->storeSecret($this->adminUser, $secretData['secret']);
        $this->mfaService->generateRecoveryCodes($this->adminUser);
        $this->mfaService->enableMfa($this->adminUser);

        $code = $this->mfaService->generateCode($secretData['secret']);
        $this->mfaService->disableMfa($this->adminUser);

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'mfa_disabled',
        ]);
    }
}
