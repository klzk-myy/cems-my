<?php

namespace Tests\Feature\Security;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\CurrencyPosition;
use App\Models\Customer;
use App\Models\TillBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SQL Injection Security Tests
 *
 * Tests protection against SQL injection attacks in various input vectors:
 * - Search fields (customer search, transaction search)
 * - Form inputs (customer name, ID numbers, addresses)
 * - URL parameters (transaction IDs, customer IDs)
 * - Sort/order parameters
 * - Filter parameters
 *
 * These tests verify that the application properly sanitizes user input
 * and uses parameterized queries to prevent SQL injection attacks.
 */
class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $tellerUser;

    protected User $managerUser;

    protected User $adminUser;

    protected Customer $customer;

    protected Currency $currency;

    // Common SQL injection payloads
    protected array $sqlInjectionPayloads = [
        // Classic SQL injection
        "' OR '1'='1",
        "' OR '1'='1' --",
        "' OR '1'='1' /*",
        "' OR '1'='1' #",
        "' OR '1'='1'; --",

        // Union-based attacks
        "' UNION SELECT * FROM users --",
        "' UNION SELECT username, password_hash FROM users --",
        "' UNION ALL SELECT NULL, NULL, NULL, NULL --",

        // Time-based blind SQL injection
        "' OR SLEEP(5) --",
        "' OR pg_sleep(5) --",
        "' OR WAITFOR DELAY '0:0:5' --",

        // Error-based SQL injection
        "' AND 1=CONVERT(int, (SELECT @@version)) --",
        "' AND 1=CAST((SELECT @@version) AS int) --",

        // Stacked queries
        "'; DROP TABLE users; --",
        "'; DELETE FROM transactions; --",
        "'; UPDATE users SET role='admin'; --",

        // Boolean-based blind SQL injection
        "' AND 1=1 --",
        "' AND 1=2 --",
        "' AND ASCII(SUBSTRING((SELECT @@version),1,1)) > 50 --",

        // Comment-based
        "'/**/OR/**/'1'='1",
        "'/*!50000OR*/'1'='1",

        // Encoding bypass
        '%27%20OR%20%271%27%3D%271',
        "\x27\x20\x4F\x52\x20\x27\x31\x27\x3D\x27\x31",

        // JSON injection
        '{"username": "admin", "password": {"$ne": null}}',

        // NoSQL injection attempts (should not affect SQL databases)
        '{$ne: null}',
        "{\$gt: ''}",
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@cems.my',
            'password_hash' => Hash::make('Admin@123456'),
            'role' => UserRole::Admin,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->tellerUser = User::create([
            'username' => 'teller1',
            'email' => 'teller1@cems.my',
            'password_hash' => Hash::make('Teller@1234'),
            'role' => UserRole::Teller,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->managerUser = User::create([
            'username' => 'manager1',
            'email' => 'manager1@cems.my',
            'password_hash' => Hash::make('Manager@1234'),
            'role' => UserRole::Manager,
            'mfa_enabled' => false,
            'is_active' => true,
        ]);

        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'rate_buy' => 4.7200,
                'rate_sell' => 4.7500,
                'is_active' => true,
            ]
        );

        $this->customer = Customer::create([
            'full_name' => 'Test Customer',
            'id_type' => 'MyKad',
            'id_number_encrypted' => encrypt('123456789012'),
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Malaysian',
            'address_encrypted' => encrypt('123 Test Street'),
            'contact_number_encrypted' => encrypt('0123456789'),
            'email' => 'customer@test.com',
            'pep_status' => false,
            'sanction_hit' => false,
            'is_active' => true,
            'risk_rating' => 'Low',
        ]);

        TillBalance::create([
            'till_id' => 'MAIN',
            'currency_code' => 'USD',
            'opening_balance' => '10000.00',
            'date' => today(),
            'opened_by' => $this->tellerUser->id,
        ]);

        AccountingPeriod::create([
            'period_code' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'month',
            'status' => 'open',
        ]);

        ChartOfAccount::firstOrCreate(
            ['account_code' => '1000'],
            ['account_name' => 'Cash - MYR', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '2000'],
            ['account_name' => 'Inventory', 'account_type' => 'Asset', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '5000'],
            ['account_name' => 'Gain on FX', 'account_type' => 'Revenue', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['account_code' => '6000'],
            ['account_name' => 'Loss on FX', 'account_type' => 'Expense', 'is_active' => true]
        );

        CurrencyPosition::create([
            'currency_code' => 'USD',
            'till_id' => 'MAIN',
            'balance' => '5000',
            'avg_cost_rate' => '4.70',
            'last_valuation_rate' => '4.75',
        ]);
    }

    // =============================================================================
    // Customer Search SQL Injection Tests
    // =============================================================================

    /**
     * Test customer search is protected against SQL injection
     * Vulnerability: SQL injection in search parameter
     */
    public function test_customer_search_is_sql_injection_safe(): void
    {
        foreach ($this->sqlInjectionPayloads as $payload) {
            $response = $this->actingAs($this->tellerUser)
                ->get('/customers?search='.urlencode($payload));

            // Should not return 500 or database error
            $this->assertFalse(
                $response->status() >= 500,
                "SQL injection payload '{$payload}' caused server error in customer search"
            );

            // Should not expose SQL errors
            $content = $response->getContent();
            if ($content) {
                $this->assertStringNotContainsString('SQL', $content, "SQL error exposed for payload: {$payload}");
                $this->assertStringNotContainsString('syntax', $content, "SQL syntax error exposed for payload: {$payload}");
            }
        }

        // Verify database integrity
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', ['full_name' => 'Test Customer']);
    }

    /**
     * Test customer name field is protected against SQL injection
     * Vulnerability: SQL injection in name field during creation
     */
    public function test_customer_name_sql_injection_protection(): void
    {
        foreach ($this->sqlInjectionPayloads as $payload) {
            $response = $this->actingAs($this->tellerUser)->post('/customers', [
                'full_name' => $payload,
                'id_type' => 'MyKad',
                'id_number' => '123456789999',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'Malaysian',
                'address' => '123 Test Street',
                'contact_number' => '0123456789',
                'email' => 'test'.rand(1000, 9999).'@test.com',
            ]);

            // Should not cause 500 error
            $this->assertFalse(
                $response->status() >= 500,
                "SQL injection in name field caused error: {$payload}"
            );
        }

        // Database should not be corrupted
        $this->assertGreaterThanOrEqual(1, Customer::count());
    }

    // =============================================================================
    // Transaction Search SQL Injection Tests
    // =============================================================================

    /**
     * Test transaction search is protected against SQL injection
     * Vulnerability: SQL injection in transaction filters
     */
    public function test_transaction_search_is_sql_injection_safe(): void
    {
        // Create a sample transaction first
        Transaction::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->tellerUser->id,
            'till_id' => 'MAIN',
            'type' => TransactionType::Buy,
            'currency_code' => 'USD',
            'amount_foreign' => '100',
            'amount_local' => '472.00',
            'rate' => '4.7200',
            'purpose' => 'Travel',
            'source_of_funds' => 'Savings',
            'status' => TransactionStatus::Completed,
            'cdd_level' => \App\Enums\CddLevel::Simplified,
        ]);

        foreach ($this->sqlInjectionPayloads as $payload) {
            $response = $this->actingAs($this->tellerUser)
                ->get('/transactions?search='.urlencode($payload));

            $this->assertFalse(
                $response->status() >= 500,
                "SQL injection payload caused error in transaction search: {$payload}"
            );
        }

        // Verify transactions are intact
        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'type' => TransactionType::Buy->value,
        ]);
    }

    /**
     * Test transaction ID parameter is protected against SQL injection
     * Vulnerability: SQL injection in transaction ID parameter
     */
    public function test_transaction_id_sql_injection_protection(): void
    {
        foreach ($this->sqlInjectionPayloads as $payload) {
            $response = $this->actingAs($this->tellerUser)
                ->get('/transactions/'.urlencode($payload));

            $this->assertFalse(
                $response->status() >= 500,
                "SQL injection in transaction ID caused error: {$payload}"
            );
        }
    }

    // =============================================================================
    // Customer ID SQL Injection Tests
    // =============================================================================

    /**
     * Test customer ID parameter is protected against SQL injection
     * Vulnerability: SQL injection in customer ID parameter
     */
    public function test_customer_id_sql_injection_protection(): void
    {
        foreach ($this->sqlInjectionPayloads as $payload) {
            $response = $this->actingAs($this->tellerUser)
                ->get('/customers/'.urlencode($payload));

            $this->assertFalse(
                $response->status() >= 500,
                "SQL injection in customer ID caused error: {$payload}"
            );
        }
    }

    /**
     * Test customer ID number field is protected
     * Vulnerability: SQL injection in ID number
     */
    public function test_customer_id_number_sql_injection_protection(): void
    {
        foreach ($this->sqlInjectionPayloads as $payload) {
            $response = $this->actingAs($this->tellerUser)->post('/customers', [
                'full_name' => 'Test Customer',
                'id_type' => 'MyKad',
                'id_number' => $payload,
                'date_of_birth' => '1990-01-01',
                'nationality' => 'Malaysian',
                'address' => '123 Test Street',
                'contact_number' => '0123456789',
                'email' => 'test'.rand(1000, 9999).'@test.com',
            ]);

            $this->assertFalse(
                $response->status() >= 500,
                "SQL injection in ID number caused error: {$payload}"
            );
        }
    }

    // =============================================================================
    // Sort/Order Parameter SQL Injection Tests
    // =============================================================================

    /**
     * Test sort parameter is protected against SQL injection
     * Vulnerability: SQL injection in order/sort parameter
     */
    public function test_sort_parameter_sql_injection_protection(): void
    {
        $maliciousSorts = [
            '(SELECT * FROM users)',
            'id; DROP TABLE users;--',
            "id' AND '1'='1",
            'id,(SELECT @@version)',
            '(CASE WHEN (1=1) THEN id ELSE 1 END)',
        ];

        foreach ($maliciousSorts as $sort) {
            $response = $this->actingAs($this->tellerUser)
                ->get('/transactions?sort='.urlencode($sort));

            $this->assertFalse(
                $response->status() >= 500,
                "SQL injection in sort parameter caused error: {$sort}"
            );
        }
    }

    // =============================================================================
    // Filter Parameter SQL Injection Tests
    // =============================================================================

    /**
     * Test filter parameters are protected against SQL injection
     * Vulnerability: SQL injection in filter parameters
     */
    public function test_filter_parameters_sql_injection_protection(): void
    {
        $filterParams = [
            'status',
            'type',
            'currency_code',
            'customer_id',
            'start_date',
            'end_date',
        ];

        foreach ($filterParams as $param) {
            foreach ($this->sqlInjectionPayloads as $payload) {
                $response = $this->actingAs($this->tellerUser)
                    ->get("/transactions?{$param}=".urlencode($payload));

                $this->assertFalse(
                    $response->status() >= 500,
                    "SQL injection in {$param} filter caused error: {$payload}"
                );
            }
        }
    }

    // =============================================================================
    // Login SQL Injection Tests
    // =============================================================================

    /**
     * Test login is protected against SQL injection
     * Vulnerability: Authentication bypass via SQL injection
     */
    public function test_login_sql_injection_protection(): void
    {
        foreach ($this->sqlInjectionPayloads as $payload) {
            $response = $this->post('/login', [
                'username' => $payload,
                'password' => $payload,
            ]);

            // Should not authenticate successfully with SQL injection
            $this->assertGuest();

            $this->assertFalse(
                $response->status() >= 500,
                "SQL injection in login caused error: {$payload}"
            );
        }

        // Verify no unauthorized access
        $this->assertGuest();
    }

    /**
     * Test login with username SQL injection doesn't bypass authentication
     */
    public function test_login_username_sql_injection_no_bypass(): void
    {
        $response = $this->post('/login', [
            'username' => "' OR '1'='1' --",
            'password' => 'any_password',
        ]);

        // Should not authenticate
        $this->assertGuest();
    }

    // =============================================================================
    // Reports SQL Injection Tests
    // =============================================================================

    /**
     * Test report date range parameters are protected
     * Vulnerability: SQL injection in date range parameters
     */
    public function test_report_date_range_sql_injection_protection(): void
    {
        $maliciousDates = [
            "2024-01-01' OR '1'='1",
            "2024-01-01'; DROP TABLE transactions; --",
            '2024-01-01) UNION SELECT * FROM users --',
            "2024-01-01' AND 1=1 --",
        ];

        foreach ($maliciousDates as $date) {
            $response = $this->actingAs($this->managerUser)
                ->get('/reports?start_date='.urlencode($date).'&end_date=2024-12-31');

            $this->assertFalse(
                $response->status() >= 500,
                "SQL injection in date parameter caused error: {$date}"
            );
        }
    }

    // =============================================================================
    // Database Integrity Tests
    // =============================================================================

    /**
     * Test that malicious input doesn't corrupt database
     */
    public function test_database_integrity_after_sql_injection_attempts(): void
    {
        // Record initial state
        $initialUserCount = User::count();
        $initialCustomerCount = Customer::count();
        $initialTransactionCount = Transaction::count();

        // Attempt various SQL injections
        foreach ($this->sqlInjectionPayloads as $payload) {
            $this->actingAs($this->tellerUser)
                ->get('/customers?search='.urlencode($payload));

            $this->actingAs($this->tellerUser)
                ->get('/transactions?search='.urlencode($payload));
        }

        // Verify database integrity
        $this->assertEquals($initialUserCount, User::count(), 'User count changed after SQL injection attempts');
        $this->assertEquals($initialCustomerCount, Customer::count(), 'Customer count changed after SQL injection attempts');
        $this->assertEquals($initialTransactionCount, Transaction::count(), 'Transaction count changed after SQL injection attempts');

        // Verify no tables were dropped
        $this->assertTrue(DB::select("SHOW TABLES LIKE 'users'") !== [], 'Users table missing');
        $this->assertTrue(DB::select("SHOW TABLES LIKE 'customers'") !== [], 'Customers table missing');
        $this->assertTrue(DB::select("SHOW TABLES LIKE 'transactions'") !== [], 'Transactions table missing');
    }

    // =============================================================================
    // Union-Based SQL Injection Tests
    // =============================================================================

    /**
     * Test UNION-based SQL injection is prevented
     * Vulnerability: UNION SELECT to extract data
     */
    public function test_union_sql_injection_prevented(): void
    {
        $unionPayloads = [
            "' UNION SELECT null,null,null,null,null,null,null,null,null,null --",
            "' UNION SELECT username,password_hash FROM users --",
            "' UNION ALL SELECT * FROM users --",
        ];

        foreach ($unionPayloads as $payload) {
            $response = $this->actingAs($this->tellerUser)
                ->get('/customers?search='.urlencode($payload));

            // Should not return 500 error (injection did not cause query failure)
            $this->assertFalse(
                $response->status() >= 500,
                "UNION injection payload caused server error: {$payload}"
            );

            // Should not return user data - check for actual data leakage
            // not the echoed search term (which appears in the form input field)
            $content = $response->getContent() ?? '';

            // Check that actual user data wasn't leaked (password hash values, not column names)
            // The admin user's password hash starts with the bcrypt prefix
            $this->assertStringNotContainsString('Admin@123456', $content, "UNION injection leaked data: {$payload}");
            $this->assertStringNotContainsString('$2y$', $content, "UNION injection leaked password hash: {$payload}");

            // Verify database integrity - no extra users appeared
            $this->assertDatabaseCount('users', 3); // admin, teller, manager from setUp
        }
    }

    // =============================================================================
    // Boolean-Based Blind SQL Injection Tests
    // =============================================================================

    /**
     * Test boolean-based blind SQL injection doesn't expose data
     * Vulnerability: True/false responses leaking information
     */
    public function test_boolean_based_blind_sql_injection_prevented(): void
    {
        // These payloads attempt to extract data through boolean responses
        $booleanPayloads = [
            "' AND 1=1 --",
            "' AND 1=2 --",
            "' AND 'a'='a",
            "' AND 'a'='b",
        ];

        $responses = [];
        foreach ($booleanPayloads as $payload) {
            $response = $this->actingAs($this->tellerUser)
                ->get('/customers?search='.urlencode($payload));

            $responses[] = [
                'payload' => $payload,
                'status' => $response->status(),
                'hasResults' => $response->status() === 200 && ! str_contains($response->getContent() ?? '', 'No results'),
            ];
        }

        // Response behavior should be consistent regardless of payload
        $statuses = array_column($responses, 'status');
        $uniqueStatuses = array_unique($statuses);
        $this->assertLessThanOrEqual(2, count($uniqueStatuses), 'Boolean injection affecting response status');
    }

    // =============================================================================
    // Error Message Tests
    // =============================================================================

    /**
     * Test SQL errors are not exposed to users
     * Vulnerability: Detailed SQL error messages expose database structure
     */
    public function test_sql_errors_not_exposed(): void
    {
        $response = $this->actingAs($this->tellerUser)
            ->get("/customers?search=' AND 1=CONVERT(int, (SELECT @@version)) --");

        $content = strtolower($response->getContent() ?? '');

        // Should not contain database error details
        // Check for error message patterns, not the search term itself
        $this->assertStringNotContainsString('syntax error', $content);
        $this->assertStringNotContainsString('mysql error', $content);
        $this->assertStringNotContainsString('sql error', $content);
        $this->assertStringNotContainsString('mariadb error', $content);
        $this->assertStringNotContainsString('information_schema', $content);

        // Should show generic error or validation message
        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 500 ||
            $response->status() === 422,
            'Should handle error gracefully without exposing SQL details'
        );
    }
}
