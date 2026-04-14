<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Refresh database if using RefreshDatabase
        // Database is already refreshed via trait in feature tests
    }

    /**
     * Get a test user with the specified role.
     */
    protected function getTestUser(string $role = 'teller'): \App\Models\User
    {
        return \App\Models\User::where('role', $role)->first();
    }

    /**
     * Create a test branch.
     */
    protected function createTestBranch(array $attributes = []): \App\Models\Branch
    {
        return \App\Models\Branch::create(array_merge([
            'code' => 'TEST-'.uniqid(),
            'name' => 'Test Branch',
            'address' => '123 Test Street',
            'phone' => '+60312345678',
            'email' => 'test@localhost.com',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a test counter.
     */
    protected function createTestCounter(array $attributes = []): \App\Models\Counter
    {
        return \App\Models\Counter::create(array_merge([
            'name' => 'Test Counter',
            'code' => 'CTR-'.uniqid(),
            'branch_id' => $this->createTestBranch()->id,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a test customer.
     */
    protected function createTestCustomer(array $attributes = []): \App\Models\Customer
    {
        return \App\Models\Customer::create(array_merge([
            'full_name' => 'Test Customer',
            'id_type' => 'IC',
            'id_number_encrypted' => encrypt('123456789012'),
            'nationality' => 'MY',
            'risk_rating' => 'LOW',
            'cdd_level' => 'Simplified',
            'is_active' => true,
        ], $attributes));
    }
}
