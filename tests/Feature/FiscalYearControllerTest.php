<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FiscalYearControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create(['role' => UserRole::Manager]);
    }

    #[Test]
    public function store_creates_fiscal_year(): void
    {
        $before = FiscalYear::count();

        $this->actingAs($this->manager)
            ->post('/accounting/fiscal-years', [
                'year_code' => '2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($before + 1, FiscalYear::count());
        $this->assertDatabaseHas('fiscal_years', ['year_code' => '2025']);
    }

    #[Test]
    public function store_rejects_duplicate_year_code(): void
    {
        FiscalYear::create(['year_code' => '2025', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);

        $this->actingAs($this->manager)
            ->post('/accounting/fiscal-years', [
                'year_code' => '2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
            ])
            ->assertRedirect();
    }

    #[Test]
    public function store_rejects_end_date_before_start_date(): void
    {
        $this->actingAs($this->manager)
            ->post('/accounting/fiscal-years', [
                'year_code' => '2025',
                'start_date' => '2025-06-01',
                'end_date' => '2025-01-01',
            ])
            ->assertSessionHasErrors('end_date');
    }

    #[Test]
    public function list_requires_manager_or_admin(): void
    {
        $teller = User::factory()->create(['role' => UserRole::Teller]);

        $this->actingAs($teller)
            ->get('/accounting/fiscal-years')
            ->assertStatus(403);

        $this->actingAs($this->manager)
            ->get('/accounting/fiscal-years')
            ->assertStatus(200);

        // Non-managers are blocked by the route-group middleware (403).
        $this->actingAs(User::factory()->create(['role' => UserRole::Teller]))
            ->get('/accounting/fiscal-years')
            ->assertStatus(403);
    }
}
