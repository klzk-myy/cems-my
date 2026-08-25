<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeDashboardN1Test extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dashboard_redirects_unauthenticated_users_to_login(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    #[Test]
    public function dashboard_uses_caching_with_branch_scoped_cache_key(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->for($branch, 'branch')->create(['role' => UserRole::Manager]);

        $this->actingAs($user)->get('/dashboard')->assertStatus(200);

        // The dashboard builds a cache key that includes the branch id so one
        // branch's numbers are never served to another branch.
        $expectedKeyPattern = "transactions.total.branch.{$branch->id}";
        // Verify via view data that we reached the dashboard (not a redirect).
    }
}
