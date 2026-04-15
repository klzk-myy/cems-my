<?php

namespace Database\Factories;

use App\Enums\TellerAllocationStatus;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\TellerAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TellerAllocationFactory extends Factory
{
    protected $model = TellerAllocation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'counter_id' => null,
            'currency_code' => Currency::factory(),
            'allocated_amount' => fake()->randomElement([10000, 20000, 50000, 100000]),
            'current_balance' => fake()->randomElement([10000, 20000, 50000, 100000]),
            'requested_amount' => fake()->randomElement([10000, 20000, 50000, 100000]),
            'daily_limit_myr' => fake()->randomElement([50000, 100000, 200000]),
            'daily_used_myr' => 0,
            'status' => TellerAllocationStatus::Pending,
            'session_date' => now()->toDateString(),
            'approved_by' => null,
            'approved_at' => null,
            'opened_at' => null,
            'closed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TellerAllocationStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
            'opened_at' => null,
            'closed_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TellerAllocationStatus::Active,
            'approved_by' => User::factory(),
            'approved_at' => now()->subHours(2),
            'opened_at' => now()->subHours(2),
            'closed_at' => null,
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TellerAllocationStatus::Returned,
            'approved_by' => User::factory(),
            'approved_at' => now()->subHours(4),
            'opened_at' => now()->subHours(4),
            'closed_at' => now()->subHours(2),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TellerAllocationStatus::Approved,
            'approved_by' => User::factory(),
            'approved_at' => now()->subHours(1),
            'opened_at' => null,
            'closed_at' => null,
        ]);
    }
}
