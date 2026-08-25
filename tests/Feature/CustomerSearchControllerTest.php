<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $teller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teller = User::factory()->create(['role' => UserRole::Teller]);
    }

    #[Test]
    public function search_requires_authentication(): void
    {
        $this->getJson('/customers/search?query=test')
            ->assertUnauthorized();
    }

    #[Test]
    public function search_requires_query_parameter(): void
    {
        $this->actingAs($this->teller)
            ->get('/customers/search')
            ->assertSessionHasErrors('query');
    }

    #[Test]
    public function search_query_must_be_at_least_two_characters(): void
    {
        $this->actingAs($this->teller)
            ->get('/customers/search?query=a')
            ->assertSessionHasErrors('query');
    }

    #[Test]
    public function search_returns_empty_results_when_no_match(): void
    {
        $this->actingAs($this->teller)
            ->get('/customers/search?query=nonexistentuser123')
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 0);
    }

    #[Test]
    public function quick_create_requires_authentication(): void
    {
        $this->postJson('/customers/quick-create', $this->validPayload())
            ->assertUnauthorized();
    }

    #[Test]
    public function quick_create_validates_required_fields(): void
    {
        $this->actingAs($this->teller)
            ->post('/customers/quick-create', [])
            ->assertSessionHasErrors('full_name');
    }

    #[Test]
    public function quick_create_creates_customer_with_valid_payload(): void
    {
        $this->actingAs($this->teller)
            ->postJson('/customers/quick-create', $this->validPayload())
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.full_name', 'Test User')
            ->assertJsonPath('customer.nationality', 'MY');
    }

    private function validPayload(): array
    {
        return [
            'full_name' => 'Test User',
            'id_type' => 'MyKad',
            'id_number' => '999912345678',
            'date_of_birth' => '1990-01-15',
            'nationality' => 'MY',
        ];
    }
}
