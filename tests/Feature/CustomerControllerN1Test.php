<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerControllerN1Test extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function customer_index_returns_200_for_manager(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->for($branch, 'branch')->create(['role' => UserRole::Manager]);
        Customer::factory()->count(5)->create();

        $this->actingAs($user)
            ->get('/customers')
            ->assertStatus(200)
            ->assertViewIs('customers.index')
            ->assertViewHas('customers');
    }
}
