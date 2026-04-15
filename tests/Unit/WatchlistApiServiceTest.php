<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\SanctionEntry;
use App\Models\SanctionList;
use App\Services\SanctionScreeningService;
use App\Services\WatchlistApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchlistApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WatchlistApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WatchlistApiService(app(SanctionScreeningService::class));
    }

    public function test_screen_name_with_soundex_match(): void
    {
        $sanctionList = SanctionList::factory()->create();
        $sanctionEntry = SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'John Smith',
            'soundex_code' => soundex('John Smith'),
            'status' => 'active',
        ]);

        $result = $this->service->screenNameSoundex('John Smith');

        $this->assertEquals(1.0, $result['score']);
        $this->assertEquals('soundex', $result['match_type']);
        $this->assertNotNull($result['entry']);
        $this->assertEquals($sanctionEntry->id, $result['entry']->id);
    }

    public function test_screen_name_with_metaphone_match(): void
    {
        $sanctionList = SanctionList::factory()->create();
        $sanctionEntry = SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'Robert Johnson',
            'metaphone_code' => metaphone('Robert Johnson'),
            'status' => 'active',
        ]);

        $result = $this->service->screenNameMetaphone('Robert Johnson');

        $this->assertEquals(1.0, $result['score']);
        $this->assertEquals('metaphone', $result['match_type']);
        $this->assertNotNull($result['entry']);
    }

    public function test_screen_name_with_token_match(): void
    {
        $sanctionList = SanctionList::factory()->create();
        $sanctionEntry = SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'Ahmad Farouk Hassan',
            'normalized_name' => 'ahmad farouk hassan',
            'soundex_code' => soundex('Ahmad'),
            'metaphone_code' => metaphone('Ahmad'),
            'status' => 'active',
        ]);

        $result = $this->service->screenNameToken('Farouk Ahmad Hassan');

        $this->assertGreaterThan(0, $result['score']);
        $this->assertEquals('token', $result['match_type']);
        $this->assertNotNull($result['entry']);
    }

    public function test_screen_name_returns_clear_when_no_match(): void
    {
        $sanctionList = SanctionList::factory()->create();
        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'John Smith',
            'normalized_name' => 'john smith',
            'soundex_code' => soundex('John Smith'),
            'metaphone_code' => metaphone('John Smith'),
            'status' => 'active',
        ]);

        $result = $this->service->screenNameEnhanced('Completely Different Name');

        $this->assertEquals('clear', $result['action']);
        $this->assertLessThan(0.75, $result['score']);
        $this->assertNull($result['match_type']);
    }

    public function test_screen_customer_returns_block_when_high_match(): void
    {
        $sanctionList = SanctionList::factory()->create();
        $customer = Customer::factory()->create(['full_name' => 'John Smith']);

        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'John Smith',
            'normalized_name' => 'john smith',
            'soundex_code' => soundex('John Smith'),
            'metaphone_code' => metaphone('John Smith'),
            'status' => 'active',
        ]);

        $result = $this->service->screenCustomer($customer);

        $this->assertContains($result['action'], ['flag', 'block']);
        $this->assertGreaterThan(0.75, $result['score']);
    }

    public function test_screening_result_saved_to_history(): void
    {
        $sanctionList = SanctionList::factory()->create();
        $customer = Customer::factory()->create(['full_name' => 'John Smith']);

        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'John Smith',
            'normalized_name' => 'john smith',
            'soundex_code' => soundex('John Smith'),
            'metaphone_code' => metaphone('John Smith'),
            'status' => 'active',
        ]);

        $this->service->screenCustomer($customer);

        $history = $this->service->getScreeningHistory($customer);
        $this->assertGreaterThanOrEqual(1, $history->count());
    }

    public function test_soundex_no_match_different_names(): void
    {
        $sanctionList = SanctionList::factory()->create();
        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'John Smith',
            'soundex_code' => soundex('John Smith'),
            'status' => 'active',
        ]);

        $result = $this->service->screenNameSoundex('Jane Doe');

        $this->assertEquals(0, $result['score']);
        $this->assertNull($result['entry']);
    }

    public function test_metaphone_no_match_different_names(): void
    {
        $sanctionList = SanctionList::factory()->create();
        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'Robert Johnson',
            'metaphone_code' => metaphone('Robert Johnson'),
            'status' => 'active',
        ]);

        $result = $this->service->screenNameMetaphone('Amanda Smith');

        $this->assertEquals(0, $result['score']);
        $this->assertNull($result['entry']);
    }

    public function test_enhanced_screening_combines_all_methods(): void
    {
        $sanctionList = SanctionList::factory()->create();
        SanctionEntry::factory()->create([
            'list_id' => $sanctionList->id,
            'entity_name' => 'John Smith',
            'normalized_name' => 'john smith',
            'soundex_code' => soundex('John Smith'),
            'metaphone_code' => metaphone('John Smith'),
            'status' => 'active',
        ]);

        $result = $this->service->screenNameEnhanced('John Smith');

        $this->assertGreaterThan(0, $result['score']);
        $this->assertContains($result['match_type'], ['exact', 'levenshtein', 'soundex', 'metaphone', 'token']);
        $this->assertArrayHasKey('all_matches', $result);
    }
}
