<?php

namespace Tests\Feature;

use App\Models\DataBreachAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DataBreachDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_breach_alert_created_when_threshold_exceeded(): void
    {
        $user = User::factory()->create();

        $cacheKey = "data_access:{$user->id}:127.0.0.1";
        Cache::put($cacheKey, 1001, 60);

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $this->assertDatabaseHas('data_breach_alerts', [
            'triggered_by' => $user->id,
            'alert_type' => 'Mass_Access',
        ]);
    }

    public function test_mass_export_detected_with_high_limit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/customers?export=1&limit=600');

        $response->assertStatus(200);
        $this->assertDatabaseHas('data_breach_alerts', [
            'triggered_by' => $user->id,
            'alert_type' => 'Export_Anomaly',
        ]);
    }
}