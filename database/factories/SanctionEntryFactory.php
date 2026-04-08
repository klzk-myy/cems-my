<?php

namespace Database\Factories;

use App\Models\SanctionEntry;
use App\Models\SanctionList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SanctionEntry>
 */
class SanctionEntryFactory extends Factory
{
    protected $model = SanctionEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'list_id' => SanctionList::factory(),
            'entity_name' => $this->faker->name(),
            'entity_type' => $this->faker->randomElement(['Individual', 'Entity']),
            'aliases' => json_encode([$this->faker->name(), $this->faker->name()]),
            'nationality' => $this->faker->countryCode(),
            'date_of_birth' => $this->faker->date(),
            'details' => json_encode(['source' => 'factory']),
        ];
    }
}
