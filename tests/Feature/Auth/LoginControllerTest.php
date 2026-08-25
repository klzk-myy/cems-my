<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function show_login_form_returns_200(): void
    {
        $this->get('/login')->assertStatus(200)->assertViewIs('auth.login');
    }

    #[Test]
    public function login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'username' => 'alice',
            'password_hash' => Hash::make('correct-password'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'username' => 'alice',
            'password' => 'correct-password',
            'ip' => '127.0.0.1',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function login_with_wrong_password_returns_error(): void
    {
        User::factory()->create([
            'username' => 'bob',
            'password_hash' => Hash::make('secret'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'username' => 'bob',
            'password' => 'wrong',
            'ip' => '127.0.0.1',
        ])->assertRedirect()
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function login_with_inactive_user_returns_error(): void
    {
        User::factory()->create([
            'username' => 'inactive',
            'password_hash' => Hash::make('pass'),
            'is_active' => false,
        ]);

        $this->post('/login', [
            'username' => 'inactive',
            'password' => 'pass',
            'ip' => '127.0.0.1',
        ])->assertRedirect()->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function login_with_unknown_username_returns_error(): void
    {
        $this->post('/login', [
            'username' => 'nobody',
            'password' => 'pass',
            'ip' => '127.0.0.1',
        ])->assertRedirect()->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
