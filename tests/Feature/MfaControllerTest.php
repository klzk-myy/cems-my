<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MfaControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'username' => 'alice',
            'password_hash' => Hash::make('pass'),
            'is_active' => true,
            'mfa_enabled' => false,
        ]);
    }

    #[Test]
    public function setup_page_requires_authentication(): void
    {
        $this->get('/mfa/setup')->assertRedirect('/login');
    }

    #[Test]
    public function setup_page_loads_for_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get('/mfa/setup')
            ->assertStatus(200);
    }

    #[Test]
    public function verify_page_redirects_to_setup_when_mfa_not_enabled(): void
    {
        $this->actingAs($this->user)
            ->get('/mfa/verify')
            ->assertRedirect('/mfa/setup');
    }

    #[Test]
    public function recovery_page_loads_for_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get('/mfa/recovery')
            ->assertStatus(200);
    }
}
