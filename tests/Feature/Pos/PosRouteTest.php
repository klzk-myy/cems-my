<?php

namespace Tests\Feature\Pos;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_rates_index_route_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/pos/rates');

        if ($response->status() !== 200) {
            dump($response->exception ?? $response->getContent());
        }

        $response->assertStatus(200);
    }

    public function test_pos_transactions_create_route_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/pos/transactions/create');

        if ($response->status() !== 200) {
            dump($response->exception ?? $response->getContent());
        }

        $response->assertStatus(200);
    }

    public function test_pos_inventory_index_route_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/pos/inventory');

        $response->assertStatus(200);
    }

    public function test_pos_routes_require_authentication(): void
    {
        $response = $this->get('/pos/rates');
        $response->assertRedirect('/login');

        $response = $this->get('/pos/transactions/create');
        $response->assertRedirect('/login');

        $response = $this->get('/pos/inventory');
        $response->assertRedirect('/login');
    }
}
