<?php

namespace Database\Factories;

use App\Modules\Pos\Models\PosDailyRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PosDailyRateFactory extends Factory
{
    protected $model = PosDailyRate::class;

    public function definition(): array
    {
        return [
            'rate_date' => $this->faker->date(),
            'currency_code' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'SGD', 'JPY']),
            'buy_rate' => $this->faker->randomFloat(6, 1, 10),
            'sell_rate' => $this->faker->randomFloat(6, 1, 10),
            'mid_rate' => $this->faker->randomFloat(6, 1, 10),
            'is_active' => true,
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
