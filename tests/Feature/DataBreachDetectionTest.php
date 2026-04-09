<?php

namespace Tests\Feature;

use App\Models\Customer;
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
        $customer = Customer::factory()->create();

        // Pre-fill cache above threshold
        $cacheKey = "data_access:{$user->id}:127.0.0.1";
        Cache::put($cacheKey, 1001, 60);

        $this->actingAs($user);

        // Hit /customers/{customer} which IS protected by data.breach middleware
        $response = $this->get("/customers/{$customer->id}");

        $response->assertStatus(200);

        $alert = DataBreachAlert::where('triggered_by', $user->id)
            ->where('alert_type', 'Mass_Access')
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals(1002, $alert->record_count); // 1001 + 1 increment
        $this->assertEquals('127.0.0.1', $alert->ip_address);
        $this->assertEquals('Critical', $alert->severity);
        $this->assertFalse($alert->is_resolved);
    }

    public function test_mass_export_detected_with_high_limit(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $this->actingAs($user);

        // Hit /customers/{customer}/history/export which IS protected
        $response = $this->get("/customers/{$customer->id}/history/export?export=1&limit=600");

        $response->assertStatus(200);

        $alert = DataBreachAlert::where('triggered_by', $user->id)
            ->where('alert_type', 'Export_Anomaly')
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals(0, $alert->record_count);
        $this->assertEquals('127.0.0.1', $alert->ip_address);
        $this->assertEquals('Critical', $alert->severity);
        $this->assertFalse($alert->is_resolved);
    }

    public function test_no_alert_when_below_threshold(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        // Pre-fill cache below threshold
        $cacheKey = "data_access:{$user->id}:127.0.0.1";
        Cache::put($cacheKey, 50, 60);

        $this->actingAs($user);

        $response = $this->get("/customers/{$customer->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('data_breach_alerts', [
            'triggered_by' => $user->id,
            'alert_type' => 'Mass_Access',
        ]);
    }
}