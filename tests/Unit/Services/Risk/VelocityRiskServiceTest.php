<?php

namespace Tests\Unit\Services\Risk;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Risk\VelocityRiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VelocityRiskServiceTest extends TestCase
{
    use RefreshDatabase;

    private VelocityRiskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VelocityRiskService::class);
    }

    #[Test]
    public function calculate_score_returns_zero_when_no_transactions(): void
    {
        $customer = Customer::factory()->create();

        $this->assertSame(0, $this->service->calculateScore($customer->id, 24));
    }

    #[Test]
    public function check_threshold_returns_triggered_true_when_count_meets_threshold(): void
    {
        $customer = Customer::factory()->create();

        Transaction::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'status' => TransactionStatus::Completed->value,
            'created_at' => now()->subHours(2),
        ]);

        $result = $this->service->checkThreshold($customer->id, 24, 3);

        $this->assertTrue($result['triggered']);
        $this->assertSame(3, $result['count']);
    }

    #[Test]
    public function get_24h_count_ignores_transactions_outside_window(): void
    {
        $customer = Customer::factory()->create();

        Transaction::factory()->count(5)->create([
            'customer_id' => $customer->id,
            'status' => TransactionStatus::Completed->value,
            'created_at' => now()->subDays(2),
        ]);

        $this->assertSame(0, $this->service->get24hCount($customer->id));
    }
}
