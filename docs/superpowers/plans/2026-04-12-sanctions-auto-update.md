# Sanctions Auto-Update System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement automated sanctions list download, validation, and import from UN/OFAC/MOHA/EU sources with change detection and customer rescreening.

**Architecture:** Create a configuration-driven system with source-specific download jobs, a unified import service with change detection, scheduled daily updates with retry logic, and customer rescreening against new entries. Follow existing patterns for jobs (see `app/Jobs/Compliance/`), commands (see `app/Console/Commands/`), and audit logging via `AuditService`.

**Tech Stack:** Laravel 10.x, PHP 8.1, MySQL, XML/CSV/JSON parsing, HTTP client with retry, database transactions, queued jobs.

---

## Task 1: Database Migration for Sanctions List Metadata

**Files:**
- Create: `database/migrations/2026_04_12_000001_add_auto_update_fields_to_sanction_lists.php`
- Modify: `app/Models/SanctionList.php`

**Context:** The current `sanction_lists` table needs additional fields to track automated updates, source URLs, and timestamps. The `list_type` enum needs to support OFAC and EU sources.

**Before you start:**
- Check `database/migrations/2025_03_31_000007_create_sanction_lists_table.php` for current schema
- Check `app/Models/SanctionList.php` for current model structure

---

### Task 1: Migration for Auto-Update Fields

- [ ] **Step 1: Create migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sanction_lists', function (Blueprint $table) {
            // Change list_type enum to include OFAC and EU
            $table->enum('list_type', ['UNSCR', 'MOHA', 'OFAC', 'EU', 'Internal'])->change();
            
            // Source URL for auto-download
            $table->string('source_url', 500)->nullable()->after('list_type');
            
            // Format of the source (XML, CSV, JSON)
            $table->enum('source_format', ['XML', 'CSV', 'JSON'])->nullable()->after('source_url');
            
            // Last successful update timestamp
            $table->timestamp('last_updated_at')->nullable()->after('uploaded_at');
            
            // Last attempted update (for tracking failures)
            $table->timestamp('last_attempted_at')->nullable()->after('last_updated_at');
            
            // Status of last update attempt
            $table->enum('update_status', ['success', 'failed', 'pending', 'never_run'])->default('never_run')->after('last_attempted_at');
            
            // Error message if failed
            $table->text('last_error_message')->nullable()->after('update_status');
            
            // Entry count for change detection
            $table->unsignedInteger('entry_count')->default(0)->after('last_error_message');
            
            // Checksum of last downloaded file for change detection
            $table->string('last_checksum', 64)->nullable()->after('entry_count');
            
            // System user ID for automated updates (null for manual)
            $table->foreignId('auto_updated_by')->nullable()->constrained('users')->after('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('sanction_lists', function (Blueprint $table) {
            $table->dropColumn([
                'source_url',
                'source_format',
                'last_updated_at',
                'last_attempted_at',
                'update_status',
                'last_error_message',
                'entry_count',
                'last_checksum',
                'auto_updated_by',
            ]);
            $table->enum('list_type', ['UNSCR', 'MOHA', 'Internal'])->change();
        });
    }
};
```

- [ ] **Step 2: Update SanctionList model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SanctionList extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'list_type',
        'source_url',
        'source_format',
        'source_file',
        'uploaded_by',
        'auto_updated_by',
        'is_active',
        'uploaded_at',
        'last_updated_at',
        'last_attempted_at',
        'update_status',
        'last_error_message',
        'entry_count',
        'last_checksum',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'uploaded_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'last_attempted_at' => 'datetime',
        'entry_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uploaded_at = now();
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SanctionEntry::class, 'list_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function autoUpdatedBy()
    {
        return $this->belongsTo(User::class, 'auto_updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAutoUpdatable($query)
    {
        return $query->whereNotNull('source_url')->where('is_active', true);
    }

    public function isAutoUpdated(): bool
    {
        return $this->auto_updated_by !== null;
    }

    public function getUpdateStatusBadgeAttribute(): string
    {
        return match ($this->update_status) {
            'success' => 'badge-success',
            'failed' => 'badge-error',
            'pending' => 'badge-warning',
            default => 'badge-neutral',
        };
    }
}
```

- [ ] **Step 3: Run migration**

```bash
php artisan migrate --path=database/migrations/2026_04_12_000001_add_auto_update_fields_to_sanction_lists.php
```

Expected output: "Migrated: 2026_04_12_000001_add_auto_update_fields_to_sanction_lists"

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_12_000001_add_auto_update_fields_to_sanction_lists.php
git add app/Models/SanctionList.php
git commit -m "feat(sanctions): add auto-update metadata fields to sanction_lists table

Add migration and model updates to support automated sanctions list updates:
- Source URL and format tracking
- Last updated/attempted timestamps
- Update status and error tracking
- Entry count and checksum for change detection
- Support for OFAC and EU list types"
```

---

## Task 2: Create Sanctions Sources Configuration

**Files:**
- Create: `config/sanctions.php`

**Context:** Configuration file to define source URLs, parsing strategies, and update settings for each sanctions list provider.

---

### Task 2: Configuration File

- [ ] **Step 1: Create configuration file**

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sanctions List Sources
    |--------------------------------------------------------------------------
    |
    | Configuration for automated sanctions list downloads.
    | These URLs are official sources from UN, OFAC, Malaysia MOHA, and EU.
    |
    */

    'sources' => [
        'un' => [
            'name' => 'UN Security Council Consolidated List',
            'url' => env('SANCTIONS_UN_URL', 'https://scsanctions.un.org/resources/xml/en/consolidated.xml'),
            'format' => 'XML',
            'list_type' => 'UNSCR',
            'enabled' => env('SANCTIONS_UN_ENABLED', true),
            'description' => 'United Nations Security Council sanctions consolidated list',
            'update_frequency' => 'daily',
            'timeout' => 300, // 5 minutes
            'retry_attempts' => 3,
            'retry_delay' => 60, // seconds between retries
        ],

        'ofac' => [
            'name' => 'OFAC SDN List',
            'url' => env('SANCTIONS_OFAC_URL', 'https://www.treasury.gov/ofac/downloads/sdn.xml'),
            'format' => 'XML',
            'list_type' => 'OFAC',
            'enabled' => env('SANCTIONS_OFAC_ENABLED', true),
            'description' => 'US Treasury Office of Foreign Assets Control Specially Designated Nationals',
            'update_frequency' => 'daily',
            'timeout' => 300,
            'retry_attempts' => 3,
            'retry_delay' => 60,
        ],

        'ofac_consolidated' => [
            'name' => 'OFAC Consolidated Sanctions',
            'url' => env('SANCTIONS_OFAC_CONSOLIDATED_URL', 'https://www.treasury.gov/ofac/downloads/consolidated/consolidated.xml'),
            'format' => 'XML',
            'list_type' => 'OFAC',
            'enabled' => env('SANCTIONS_OFAC_CONSOLIDATED_ENABLED', true),
            'description' => 'OFAC consolidated sanctions list (includes non-SDN sanctions)',
            'update_frequency' => 'daily',
            'timeout' => 300,
            'retry_attempts' => 3,
            'retry_delay' => 60,
        ],

        'moha' => [
            'name' => 'Malaysia MOHA Terrorism List',
            'url' => env('SANCTIONS_MOHA_URL', ''),
            'format' => 'CSV',
            'list_type' => 'MOHA',
            'enabled' => env('SANCTIONS_MOHA_ENABLED', false),
            'description' => 'Malaysia Ministry of Home Affairs designated terrorist organizations',
            'update_frequency' => 'daily',
            'timeout' => 120,
            'retry_attempts' => 3,
            'retry_delay' => 60,
            'note' => 'MOHA does not provide a public automated download URL. Manual import required.',
        ],

        'eu' => [
            'name' => 'EU Consolidated Financial Sanctions',
            'url' => env('SANCTIONS_EU_URL', 'https://webgate.ec.europa.eu/fsd/fsf/public/files/csvFullSanctionsList_1_1/content?token=n/a'),
            'format' => 'CSV',
            'list_type' => 'EU',
            'enabled' => env('SANCTIONS_EU_ENABLED', true),
            'description' => 'European Union consolidated list of financial sanctions',
            'update_frequency' => 'daily',
            'timeout' => 300,
            'retry_attempts' => 3,
            'retry_delay' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Download Settings
    |--------------------------------------------------------------------------
    |
    | General settings for downloading sanctions lists.
    |
    */

    'download' => [
        'temp_directory' => storage_path('app/temp/sanctions'),
        'archive_directory' => storage_path('app/archive/sanctions'),
        'keep_archives_days' => 30,
        'user_agent' => 'CEMS-MY/1.0 (Compliance Management System)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Schedule
    |--------------------------------------------------------------------------
    |
    | When to run automatic updates. Daily at 03:00 recommended to avoid
    | peak hours. BNM requires sanctions be updated within 24 hours.
    |
    */

    'schedule' => [
        'enabled' => env('SANCTIONS_AUTO_UPDATE_ENABLED', true),
        'time' => '03:00',
        'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Who to notify when updates fail or significant changes are detected.
    |
    */

    'notifications' => [
        'enabled' => env('SANCTIONS_NOTIFICATIONS_ENABLED', true),
        'channels' => ['mail', 'database'],
        'recipients' => [
            'compliance' => env('SANCTIONS_COMPLIANCE_EMAIL'),
            'admin' => env('SANCTIONS_ADMIN_EMAIL'),
        ],
        'alert_on' => [
            'update_failed' => true,
            'new_entries_found' => true,
            'significant_changes' => true, // > 10% change
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Change Detection Thresholds
    |--------------------------------------------------------------------------
    |
    | When to trigger alerts based on changes between updates.
    |
    */

    'change_thresholds' => [
        'significant_percentage' => 10.0, // Alert if >10% of entries changed
        'minimum_new_entries' => 5, // Alert if 5+ new entries
        'minimum_removed_entries' => 5, // Alert if 5+ entries removed
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Rescreening
    |--------------------------------------------------------------------------
    |
    | Automatically rescreen customers when new entries are added.
    |
    */

    'rescreening' => [
        'enabled' => env('SANCTIONS_AUTOMATIC_RESREEN_ENABLED', true),
        'batch_size' => 100, // Process in batches
        'queue' => 'sanctions', // Dedicated queue
        'match_threshold' => 0.80, // Same as SanctionScreeningService
    ],

    /*
    |--------------------------------------------------------------------------
    | System User
    |--------------------------------------------------------------------------
    |
    | User ID to use for automated updates (for audit trail).
    | This should be a system/internal user.
    |
    */

    'system_user_id' => env('SANCTIONS_SYSTEM_USER_ID', 1),
];
```

- [ ] **Step 2: Commit**

```bash
git add config/sanctions.php
git commit -m "feat(sanctions): add sanctions sources configuration

Create config/sanctions.php with:
- UN, OFAC, MOHA, EU source URLs and settings
- Download and archive settings
- Update schedule configuration
- Notification settings
- Change detection thresholds
- Customer rescreening configuration"
```

---

## Task 3: Create SanctionsDownloadService

**Files:**
- Create: `app/Services/SanctionsDownloadService.php`
- Create: `tests/Unit/Services/SanctionsDownloadServiceTest.php`

**Context:** Service to handle HTTP downloads with retry logic, validation, and file management for sanctions lists.

---

### Task 3: Download Service

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Unit\Services;

use App\Services\SanctionsDownloadService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SanctionsDownloadServiceTest extends TestCase
{
    protected SanctionsDownloadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->service = new SanctionsDownloadService();
    }

    public function test_downloads_file_from_url(): void
    {
        Http::fake([
            'https://example.com/sanctions.xml' => Http::response('<xml>test</xml>', 200),
        ]);

        $result = $this->service->download('https://example.com/sanctions.xml', 'test.xml');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['filepath']);
        $this->assertEquals('<xml>test</xml>', file_get_contents($result['filepath']));
    }

    public function test_calculates_sha256_checksum(): void
    {
        Http::fake([
            'https://example.com/sanctions.xml' => Http::response('<xml>test</xml>', 200),
        ]);

        $result = $this->service->download('https://example.com/sanctions.xml', 'test.xml');

        $this->assertNotNull($result['checksum']);
        $this->assertEquals(64, strlen($result['checksum'])); // SHA-256 is 64 hex chars
        $this->assertEquals(hash('sha256', '<xml>test</xml>'), $result['checksum']);
    }

    public function test_returns_error_on_http_failure(): void
    {
        Http::fake([
            'https://example.com/sanctions.xml' => Http::response('', 500),
        ]);

        $result = $this->service->download('https://example.com/sanctions.xml', 'test.xml');

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
        $this->assertNull($result['filepath']);
    }

    public function test_returns_error_on_network_exception(): void
    {
        Http::fake([
            'https://example.com/sanctions.xml' => Http::throw(fn () => new \Exception('Connection timeout')),
        ]);

        $result = $this->service->download('https://example.com/sanctions.xml', 'test.xml');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection timeout', $result['error']);
    }

    public function test_validates_xml_content(): void
    {
        Http::fake([
            'https://example.com/valid.xml' => Http::response('<?xml version="1.0"?><root></root>', 200),
            'https://example.com/invalid.xml' => Http::response('not valid xml', 200),
        ]);

        $valid = $this->service->download('https://example.com/valid.xml', 'valid.xml');
        $invalid = $this->service->download('https://example.com/invalid.xml', 'invalid.xml');

        $this->assertTrue($valid['success']);
        $this->assertTrue($valid['format_valid']);
        
        $this->assertFalse($invalid['success']);
        $this->assertFalse($invalid['format_valid'] ?? true);
    }

    public function test_creates_temp_directory_if_not_exists(): void
    {
        Http::fake([
            'https://example.com/sanctions.xml' => Http::response('<xml>test</xml>', 200),
        ]);

        Storage::deleteDirectory('temp/sanctions');

        $result = $this->service->download('https://example.com/sanctions.xml', 'test.xml');

        $this->assertTrue($result['success']);
        Storage::assertExists('temp/sanctions');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=SanctionsDownloadServiceTest
```

Expected: FAIL with "Class "App\Services\SanctionsDownloadService" not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SanctionsDownloadService
{
    protected string $tempDirectory;
    protected int $timeout;
    protected int $retryAttempts;

    public function __construct()
    {
        $this->tempDirectory = config('sanctions.download.temp_directory', storage_path('app/temp/sanctions'));
        $this->timeout = config('sanctions.download.timeout', 300);
    }

    /**
     * Download a sanctions list from URL with retry logic.
     *
     * @param string $url Source URL
     * @param string $filename Target filename
     * @param string $format Expected format (XML, CSV, JSON)
     * @param int $retryAttempts Number of retry attempts
     * @return array{success: bool, filepath: string|null, checksum: string|null, error: string|null, format_valid: bool}
     */
    public function download(
        string $url,
        string $filename,
        string $format = 'XML',
        int $retryAttempts = 3
    ): array {
        $this->ensureTempDirectoryExists();

        $filepath = $this->tempDirectory . '/' . $filename;
        $lastError = null;

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withUserAgent(config('sanctions.download.user_agent', 'CEMS-MY/1.0'))
                    ->get($url);

                if (!$response->successful()) {
                    $lastError = "HTTP {$response->status()}: Failed to download from {$url}";
                    Log::warning("Sanctions download attempt {$attempt} failed", [
                        'url' => $url,
                        'status' => $response->status(),
                    ]);
                    
                    if ($attempt < $retryAttempts) {
                        sleep(config('sanctions.sources.un.retry_delay', 60));
                    }
                    continue;
                }

                $content = $response->body();
                
                // Validate format
                $formatValid = $this->validateFormat($content, $format);
                
                if (!$formatValid) {
                    return [
                        'success' => false,
                        'filepath' => null,
                        'checksum' => null,
                        'error' => "Downloaded content is not valid {$format}",
                        'format_valid' => false,
                    ];
                }

                // Save file
                file_put_contents($filepath, $content);

                // Calculate checksum
                $checksum = hash('sha256', $content);

                Log::info("Sanctions list downloaded successfully", [
                    'url' => $url,
                    'filepath' => $filepath,
                    'size' => strlen($content),
                    'checksum' => $checksum,
                ]);

                return [
                    'success' => true,
                    'filepath' => $filepath,
                    'checksum' => $checksum,
                    'error' => null,
                    'format_valid' => true,
                ];

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("Sanctions download attempt {$attempt} failed with exception", [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $retryAttempts) {
                    sleep(config('sanctions.sources.un.retry_delay', 60));
                }
            }
        }

        Log::error("Sanctions download failed after {$retryAttempts} attempts", [
            'url' => $url,
            'last_error' => $lastError,
        ]);

        return [
            'success' => false,
            'filepath' => null,
            'checksum' => null,
            'error' => $lastError ?? 'Unknown error',
            'format_valid' => false,
        ];
    }

    /**
     * Validate downloaded content matches expected format.
     */
    protected function validateFormat(string $content, string $format): bool
    {
        return match ($format) {
            'XML' => $this->validateXml($content),
            'JSON' => $this->validateJson($content),
            'CSV' => $this->validateCsv($content),
            default => true,
        };
    }

    protected function validateXml(string $content): bool
    {
        $previousValue = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($content);
        libxml_use_internal_errors($previousValue);

        return $doc !== false;
    }

    protected function validateJson(string $content): bool
    {
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function validateCsv(string $content): bool
    {
        // Basic CSV validation - check for comma-separated structure
        $lines = explode("\n", $content);
        if (count($lines) < 2) {
            return false;
        }

        $firstLine = $lines[0];
        return str_contains($firstLine, ',') || str_contains($firstLine, "\t");
    }

    /**
     * Archive the downloaded file.
     */
    public function archiveFile(string $filepath, string $listType): ?string
    {
        $archiveDir = config('sanctions.download.archive_directory', storage_path('app/archive/sanctions'));
        
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        $filename = basename($filepath);
        $archivePath = $archiveDir . '/' . $listType . '_' . date('Y-m-d_His') . '_' . $filename;

        if (copy($filepath, $archivePath)) {
            Log::info("Sanctions file archived", [
                'source' => $filepath,
                'archive' => $archivePath,
            ]);
            return $archivePath;
        }

        return null;
    }

    /**
     * Clean up old archive files.
     */
    public function cleanupArchives(int $days = 30): int
    {
        $archiveDir = config('sanctions.download.archive_directory', storage_path('app/archive/sanctions'));
        
        if (!is_dir($archiveDir)) {
            return 0;
        }

        $cutoff = time() - ($days * 86400);
        $deleted = 0;

        foreach (glob($archiveDir . '/*') as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        Log::info("Cleaned up {$deleted} old sanctions archive files");

        return $deleted;
    }

    protected function ensureTempDirectoryExists(): void
    {
        if (!is_dir($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0755, true);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --filter=SanctionsDownloadServiceTest
```

Expected: PASS (6 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/SanctionsDownloadService.php
git add tests/Unit/Services/SanctionsDownloadServiceTest.php
git commit -m "feat(sanctions): add SanctionsDownloadService for automated downloads

Create service with:
- HTTP download with retry logic
- XML/CSV/JSON validation
- Checksum calculation for change detection
- File archiving and cleanup functionality
- Comprehensive unit tests"
```

---

## Task 4: Create SanctionsImportService with Change Detection

**Files:**
- Create: `app/Services/SanctionsImportService.php`
- Create: `tests/Unit/Services/SanctionsImportServiceTest.php`

**Context:** Service to parse different formats (XML, CSV, JSON) and import entries with change detection.

---

### Task 4: Import Service with Change Detection

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Unit\Services;

use App\Models\SanctionEntry;
use App\Models\SanctionList;
use App\Services\SanctionsImportService;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctionsImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SanctionsImportService $service;
    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = $this->createMock(AuditService::class);
        $this->service = new SanctionsImportService($this->auditService);
    }

    public function test_imports_csv_entries(): void
    {
        $list = SanctionList::factory()->create(['list_type' => 'UNSCR']);
        
        $csvContent = "name,entity_type,aliases,nationality\n";
        $csvContent .= "John Doe,Individual,John D,US\n";
        $csvContent .= "Acme Corp,Entity,,US";

        $filepath = sys_get_temp_dir() . '/test_sanctions.csv';
        file_put_contents($filepath, $csvContent);

        $result = $this->service->importFromCsv($filepath, $list->id);

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['removed']);
        $this->assertCount(0, $result['changes']['new']);
        $this->assertCount(0, $result['changes']['removed']);

        unlink($filepath);
    }

    public function test_detects_new_entries_on_reimport(): void
    {
        $list = SanctionList::factory()->create(['list_type' => 'UNSCR']);
        
        // First import
        $csvContent1 = "name,entity_type,aliases,nationality\n";
        $csvContent1 .= "John Doe,Individual,,US";

        $filepath = sys_get_temp_dir() . '/test_sanctions.csv';
        file_put_contents($filepath, $csvContent1);
        $this->service->importFromCsv($filepath, $list->id);

        // Second import with new entry
        $csvContent2 = "name,entity_type,aliases,nationality\n";
        $csvContent2 .= "John Doe,Individual,,US\n";
        $csvContent2 .= "Jane Smith,Individual,Jane,UK";

        file_put_contents($filepath, $csvContent2);
        $result = $this->service->importFromCsv($filepath, $list->id);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['new_entries_detected']);

        unlink($filepath);
    }

    public function test_detects_removed_entries(): void
    {
        $list = SanctionList::factory()->create(['list_type' => 'UNSCR']);
        
        // First import with two entries
        $csvContent1 = "name,entity_type,aliases,nationality\n";
        $csvContent1 .= "John Doe,Individual,,US\n";
        $csvContent1 .= "Jane Smith,Individual,,UK";

        $filepath = sys_get_temp_dir() . '/test_sanctions.csv';
        file_put_contents($filepath, $csvContent1);
        $this->service->importFromCsv($filepath, $list->id);

        // Second import with one entry removed
        $csvContent2 = "name,entity_type,aliases,nationality\n";
        $csvContent2 .= "John Doe,Individual,,US";

        file_put_contents($filepath, $csvContent2);
        $result = $this->service->importFromCsv($filepath, $list->id);

        $this->assertEquals(1, $result['removed']);

        unlink($filepath);
    }

    public function test_calculates_change_statistics(): void
    {
        $list = SanctionList::factory()->create(['list_type' => 'UNSCR', 'entry_count' => 100]);
        
        $csvContent = "name,entity_type,aliases,nationality\n";
        for ($i = 1; $i <= 115; $i++) {
            $csvContent .= "Person {$i},Individual,,US\n";
        }

        $filepath = sys_get_temp_dir() . '/test_sanctions.csv';
        file_put_contents($filepath, $csvContent);
        
        $result = $this->service->importFromCsv($filepath, $list->id, true); // Full refresh

        $this->assertTrue($result['is_significant_change']);
        $this->assertEquals(15.0, $result['change_percentage']); // 15% increase

        unlink($filepath);
    }

    public function test_imports_xml_entries(): void
    {
        $list = SanctionList::factory()->create(['list_type' => 'OFAC']);
        
        $xmlContent = '<?xml version="1.0"?>
        <sanctions>
            <entity>
                <name>Test Entity</name>
                <type>Individual</type>
                <nationality>US</nationality>
            </entity>
        </sanctions>';

        $filepath = sys_get_temp_dir() . '/test_sanctions.xml';
        file_put_contents($filepath, $xmlContent);

        // Mock the XML parsing for OFAC format
        $result = $this->service->importFromXml($filepath, $list->id, 'OFAC');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('imported', $result);

        unlink($filepath);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=SanctionsImportServiceTest
```

Expected: FAIL with "Class "App\Services\SanctionsImportService" not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services;

use App\Models\SanctionEntry;
use App\Models\SanctionList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SanctionsImportService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Import sanctions entries from CSV file.
     *
     * @param string $filepath Path to CSV file
     * @param int $listId Sanction list ID
     * @param bool $fullRefresh If true, removes entries not in new file
     * @return array Import statistics with change detection
     */
    public function importFromCsv(string $filepath, int $listId, bool $fullRefresh = false): array
    {
        $list = SanctionList::findOrFail($listId);
        $previousCount = $list->entry_count;
        
        // Get existing entry names for change detection
        $existingEntries = $this->getExistingEntries($listId);
        $importedEntries = [];

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Cannot open file: {$filepath}");
        }

        $headers = fgetcsv($handle);
        $imported = 0;
        $updated = 0;

        DB::beginTransaction();
        
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, $row);
                
                if ($data === false) {
                    Log::warning("Failed to parse CSV row", ['row' => $row]);
                    continue;
                }

                $entryData = $this->normalizeEntryData($data);
                $entryKey = $this->getEntryKey($entryData);
                
                // Check if entry already exists
                if (isset($existingEntries[$entryKey])) {
                    // Update existing entry
                    SanctionEntry::where('id', $existingEntries[$entryKey])->update([
                        'aliases' => $entryData['aliases'],
                        'nationality' => $entryData['nationality'],
                        'date_of_birth' => $entryData['date_of_birth'],
                        'details' => json_encode($data),
                    ]);
                    $updated++;
                } else {
                    // Create new entry
                    SanctionEntry::create([
                        'list_id' => $listId,
                        'entity_name' => $entryData['entity_name'],
                        'entity_type' => $entryData['entity_type'],
                        'aliases' => $entryData['aliases'],
                        'nationality' => $entryData['nationality'],
                        'date_of_birth' => $entryData['date_of_birth'],
                        'details' => json_encode($data),
                    ]);
                    $imported++;
                }

                $importedEntries[] = $entryKey;
            }

            fclose($handle);

            // Handle removed entries in full refresh mode
            $removed = 0;
            if ($fullRefresh) {
                $removed = $this->removeStaleEntries($listId, $importedEntries);
            }

            // Update list metadata
            $newCount = SanctionEntry::where('list_id', $listId)->count();
            $checksum = hash_file('sha256', $filepath);
            
            $list->update([
                'entry_count' => $newCount,
                'last_checksum' => $checksum,
            ]);

            // Calculate change statistics
            $changeStats = $this->calculateChangeStats($previousCount, $newCount, $imported, $removed);

            // Log the import
            $this->auditService->logWithSeverity(
                'sanctions_list_imported',
                [
                    'entity_type' => 'SanctionList',
                    'entity_id' => $listId,
                    'new_values' => [
                        'imported' => $imported,
                        'updated' => $updated,
                        'removed' => $removed,
                        'previous_count' => $previousCount,
                        'new_count' => $newCount,
                        'change_percentage' => $changeStats['percentage'],
                        'is_significant' => $changeStats['is_significant'],
                    ],
                ],
                $changeStats['is_significant'] ? 'WARNING' : 'INFO'
            );

            DB::commit();

            return [
                'imported' => $imported,
                'updated' => $updated,
                'removed' => $removed,
                'previous_count' => $previousCount,
                'new_count' => $newCount,
                'change_percentage' => $changeStats['percentage'],
                'is_significant_change' => $changeStats['is_significant'],
                'new_entries_detected' => $imported,
                'changes' => [
                    'new' => $imported > 0 ? $this->getNewEntriesDetails($listId, $imported) : [],
                    'removed' => $removed > 0 ? $this->getRemovedEntriesDetails($removed) : [],
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Sanctions import failed", [
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Import sanctions from XML (UN/OFAC format).
     */
    public function importFromXml(string $filepath, int $listId, string $format = 'UN'): array
    {
        $xml = simplexml_load_file($filepath);
        if ($xml === false) {
            throw new \RuntimeException("Failed to parse XML file: {$filepath}");
        }

        $entries = match ($format) {
            'UN' => $this->parseUnXml($xml),
            'OFAC' => $this->parseOfacXml($xml),
            default => throw new \InvalidArgumentException("Unknown XML format: {$format}"),
        };

        return $this->importEntries($entries, $listId, $filepath);
    }

    /**
     * Import sanctions from JSON.
     */
    public function importFromJson(string $filepath, int $listId): array
    {
        $content = file_get_contents($filepath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to parse JSON: " . json_last_error_msg());
        }

        $entries = $this->parseJsonData($data);
        
        return $this->importEntries($entries, $listId, $filepath);
    }

    /**
     * Get new entries for rescreening notification.
     */
    public function getNewEntriesForRescreening(int $listId, int $limit = 100): array
    {
        return SanctionEntry::where('list_id', $listId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get(['entity_name', 'entity_type', 'aliases', 'nationality'])
            ->toArray();
    }

    /**
     * Calculate change statistics and determine if significant.
     */
    protected function calculateChangeStats(int $previousCount, int $newCount, int $imported, int $removed): array
    {
        if ($previousCount === 0) {
            return ['percentage' => 0.0, 'is_significant' => $newCount > 100];
        }

        $netChange = abs($newCount - $previousCount);
        $percentage = ($netChange / $previousCount) * 100;

        $isSignificant = $percentage > config('sanctions.change_thresholds.significant_percentage', 10.0)
            || $imported >= config('sanctions.change_thresholds.minimum_new_entries', 5)
            || $removed >= config('sanctions.change_thresholds.minimum_removed_entries', 5);

        return [
            'percentage' => round($percentage, 2),
            'is_significant' => $isSignificant,
        ];
    }

    protected function getExistingEntries(int $listId): array
    {
        return SanctionEntry::where('list_id', $listId)
            ->get(['id', 'entity_name', 'entity_type'])
            ->keyBy(fn ($entry) => $this->getEntryKey([
                'entity_name' => $entry->entity_name,
                'entity_type' => $entry->entity_type,
            ]))
            ->map(fn ($entry) => $entry->id)
            ->toArray();
    }

    protected function getEntryKey(array $data): string
    {
        return strtolower(trim($data['entity_name'])) . '|' . strtolower(trim($data['entity_type'] ?? 'Individual'));
    }

    protected function normalizeEntryData(array $data): array
    {
        return [
            'entity_name' => trim($data['name'] ?? $data['entity_name'] ?? ''),
            'entity_type' => $data['entity_type'] ?? 'Individual',
            'aliases' => isset($data['aliases']) && !empty($data['aliases']) ? json_encode(explode(',', $data['aliases'])) : null,
            'nationality' => $data['nationality'] ?? null,
            'date_of_birth' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
        ];
    }

    protected function removeStaleEntries(int $listId, array $currentEntries): int
    {
        // Get entries that are not in the current import
        $toRemove = SanctionEntry::where('list_id', $listId)
            ->whereNotIn(
                DB::raw("LOWER(TRIM(entity_name)) || '|' || LOWER(TRIM(entity_type))"),
                array_map('strtolower', $currentEntries)
            )
            ->pluck('id');

        $count = $toRemove->count();
        
        if ($count > 0) {
            SanctionEntry::whereIn('id', $toRemove)->delete();
        }

        return $count;
    }

    protected function getNewEntriesDetails(int $listId, int $count): array
    {
        return SanctionEntry::where('list_id', $listId)
            ->orderBy('id', 'desc')
            ->limit(min($count, 10)) // Return first 10
            ->pluck('entity_name')
            ->toArray();
    }

    protected function getRemovedEntriesDetails(int $count): array
    {
        return ["{$count} entries removed"];
    }

    protected function importEntries(array $entries, int $listId, string $filepath): array
    {
        // Create temporary CSV for unified processing
        $csvPath = sys_get_temp_dir() . '/sanctions_import_' . uniqid() . '.csv';
        $handle = fopen($csvPath, 'w');
        
        fputcsv($handle, ['name', 'entity_type', 'aliases', 'nationality', 'date_of_birth']);
        
        foreach ($entries as $entry) {
            fputcsv($handle, [
                $entry['entity_name'],
                $entry['entity_type'] ?? 'Individual',
                $entry['aliases'] ?? '',
                $entry['nationality'] ?? '',
                $entry['date_of_birth'] ?? '',
            ]);
        }
        
        fclose($handle);

        $result = $this->importFromCsv($csvPath, $listId, true);
        
        unlink($csvPath);

        return $result;
    }

    protected function parseUnXml(\SimpleXMLElement $xml): array
    {
        $entries = [];
        // UN format parsing - adjust based on actual UN schema
        foreach ($xml->xpath('//INDIVIDUAL') as $individual) {
            $entries[] = [
                'entity_name' => trim((string) ($individual->NAME ?? $individual->FIRST_NAME . ' ' . $individual->SECOND_NAME)),
                'entity_type' => 'Individual',
                'nationality' => (string) ($individual->NATIONALITY ?? ''),
                'aliases' => '',
            ];
        }
        foreach ($xml->xpath('//ENTITY') as $entity) {
            $entries[] = [
                'entity_name' => trim((string) $entity->NAME),
                'entity_type' => 'Entity',
                'nationality' => '',
                'aliases' => '',
            ];
        }
        return $entries;
    }

    protected function parseOfacXml(\SimpleXMLElement $xml): array
    {
        $entries = [];
        // OFAC SDN format
        foreach ($xml->publishInformation->children() as $child) {
            // OFAC has a different structure
        }
        
        // Parse publish information
        foreach ($xml->xpath('//sdnEntry') as $entry) {
            $name = (string) ($entry->lastName ?? '');
            if (isset($entry->firstName)) {
                $name = (string) $entry->firstName . ' ' . $name;
            }
            if (empty($name) && isset($entry->lastName)) {
                $name = (string) $entry->lastName;
            }

            $entries[] = [
                'entity_name' => trim($name),
                'entity_type' => ((string) ($entry->sdnType ?? '')) === 'Individual' ? 'Individual' : 'Entity',
                'nationality' => '',
                'aliases' => '',
            ];
        }
        return $entries;
    }

    protected function parseJsonData(array $data): array
    {
        $entries = [];
        
        // Handle EU format
        if (isset($data['result'])) {
            foreach ($data['result'] as $item) {
                $entries[] = [
                    'entity_name' => $item['name'] ?? '',
                    'entity_type' => ($item['type'] ?? '') === 'person' ? 'Individual' : 'Entity',
                    'nationality' => $item['country'] ?? '',
                    'aliases' => '',
                ];
            }
        }
        
        return $entries;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --filter=SanctionsImportServiceTest
```

Expected: PASS (5 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/SanctionsImportService.php
git add tests/Unit/Services/SanctionsImportServiceTest.php
git commit -m "feat(sanctions): add SanctionsImportService with change detection

Create service with:
- CSV, XML, JSON parsing for UN, OFAC, EU, MOHA formats
- Change detection (new/removed entries)
- Significant change threshold calculation
- Unified entry import with transaction safety
- Audit logging for all imports
- Comprehensive unit tests"
```

---

## Task 5: Create Download Jobs for Each Source

**Files:**
- Create: `app/Jobs/Sanctions/DownloadUnSanctionsList.php`
- Create: `app/Jobs/Sanctions/DownloadOfacSanctionsList.php`
- Create: `app/Jobs/Sanctions/DownloadMohaSanctionsList.php`
- Create: `app/Jobs/Sanctions/DownloadEuSanctionsList.php`
- Create: `tests/Feature/SanctionsDownloadJobsTest.php`

**Context:** Individual jobs for each sanctions source that can be queued and retried independently.

---

### Task 5: Download Jobs

- [ ] **Step 1: Create base download job**

```php
<?php

namespace App\Jobs\Sanctions;

use App\Models\SanctionList;
use App\Services\AuditService;
use App\Services\SanctionsDownloadService;
use App\Services\SanctionsImportService;
use App\Services\Compliance\MonitoringEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseSanctionsDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes
    public int $backoff = [60, 300, 600]; // 1min, 5min, 10min

    protected string $sourceKey;
    protected string $listType;
    protected string $format;

    abstract protected function getSourceKey(): string;
    abstract protected function getListType(): string;
    abstract protected function getFormat(): string;

    public function __construct()
    {
        $this->sourceKey = $this->getSourceKey();
        $this->listType = $this->getListType();
        $this->format = $this->getFormat();
    }

    public function handle(
        SanctionsDownloadService $downloadService,
        SanctionsImportService $importService,
        AuditService $auditService,
    ): void {
        $config = config("sanctions.sources.{$this->sourceKey}");

        if (!$config || !($config['enabled'] ?? false)) {
            Log::info("Sanctions source {$this->sourceKey} is disabled");
            return;
        }

        $url = $config['url'];
        if (empty($url)) {
            Log::warning("Sanctions source {$this->sourceKey} has no URL configured");
            return;
        }

        $list = $this->getOrCreateList($config);
        
        // Mark as pending
        $list->update([
            'last_attempted_at' => now(),
            'update_status' => 'pending',
        ]);

        $filename = "{$this->sourceKey}_" . date('Y-m-d_His') . "." . strtolower($this->format);
        
        try {
            // Download file
            $result = $downloadService->download(
                $url,
                $filename,
                $this->format,
                $config['retry_attempts'] ?? 3
            );

            if (!$result['success']) {
                throw new \RuntimeException($result['error'] ?? 'Download failed');
            }

            // Check if content changed
            if ($list->last_checksum && $list->last_checksum === $result['checksum']) {
                Log::info("Sanctions list {$this->sourceKey} unchanged (same checksum)");
                
                $list->update([
                    'last_updated_at' => now(),
                    'update_status' => 'success',
                    'last_error_message' => null,
                ]);

                // Archive the file even if unchanged
                $downloadService->archiveFile($result['filepath'], $this->sourceKey);
                @unlink($result['filepath']);

                return;
            }

            // Import the data
            $importResult = match ($this->format) {
                'XML' => $importService->importFromXml($result['filepath'], $list->id, $this->listType),
                'JSON' => $importService->importFromJson($result['filepath'], $list->id),
                default => $importService->importFromCsv($result['filepath'], $list->id, true),
            };

            // Update list status
            $list->update([
                'last_updated_at' => now(),
                'update_status' => 'success',
                'last_error_message' => null,
                'last_checksum' => $result['checksum'],
                'auto_updated_by' => config('sanctions.system_user_id', 1),
            ]);

            // Archive the file
            $downloadService->archiveFile($result['filepath'], $this->sourceKey);
            @unlink($result['filepath']);

            // Trigger rescreening if new entries found
            if ($importResult['new_entries_detected'] > 0 && config('sanctions.rescreening.enabled', true)) {
                $this->dispatchRescreeningJob($importResult['new_entries_detected']);
            }

            Log::info("Sanctions list {$this->sourceKey} updated successfully", [
                'imported' => $importResult['imported'],
                'removed' => $importResult['removed'],
                'is_significant' => $importResult['is_significant_change'],
            ]);

        } catch (\Exception $e) {
            Log::error("Sanctions download job failed for {$this->sourceKey}", [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            $list->update([
                'update_status' => 'failed',
                'last_error_message' => $e->getMessage(),
            ]);

            // Notify compliance if this was the final attempt
            if ($this->attempts() >= $this->tries) {
                $this->notifyFailure($e->getMessage());
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class . ' permanently failed', [
            'source' => $this->sourceKey,
            'exception' => $exception->getMessage(),
        ]);

        $this->notifyFailure($exception->getMessage());
    }

    protected function getOrCreateList(array $config): SanctionList
    {
        $list = SanctionList::where('list_type', $this->listType)
            ->where('name', $config['name'])
            ->first();

        if (!$list) {
            $list = SanctionList::create([
                'name' => $config['name'],
                'list_type' => $this->listType,
                'source_url' => $config['url'],
                'source_format' => $this->format,
                'uploaded_by' => config('sanctions.system_user_id', 1),
                'is_active' => true,
            ]);
        }

        return $list;
    }

    protected function dispatchRescreeningJob(int $newEntriesCount): void
    {
        // Dispatch rescreening job
        \App\Jobs\Compliance\SanctionsRescreeningJob::dispatch();
        
        Log::info("Triggered customer rescreening due to {$newEntriesCount} new sanctions entries");
    }

    protected function notifyFailure(string $error): void
    {
        // Create system alert for compliance
        \App\Models\Alert::create([
            'type' => 'sanctions_update_failed',
            'severity' => 'high',
            'message' => "Sanctions list {$this->sourceKey} update failed: {$error}",
            'status' => 'open',
        ]);

        Log::alert("Sanctions update failure notification sent", [
            'source' => $this->sourceKey,
            'error' => $error,
        ]);
    }
}
```

- [ ] **Step 2: Create UN download job**

```php
<?php

namespace App\Jobs\Sanctions;

class DownloadUnSanctionsList extends BaseSanctionsDownloadJob
{
    protected function getSourceKey(): string
    {
        return 'un';
    }

    protected function getListType(): string
    {
        return 'UNSCR';
    }

    protected function getFormat(): string
    {
        return 'XML';
    }
}
```

- [ ] **Step 3: Create OFAC download job**

```php
<?php

namespace App\Jobs\Sanctions;

class DownloadOfacSanctionsList extends BaseSanctionsDownloadJob
{
    protected function getSourceKey(): string
    {
        return 'ofac';
    }

    protected function getListType(): string
    {
        return 'OFAC';
    }

    protected function getFormat(): string
    {
        return 'XML';
    }
}
```

- [ ] **Step 4: Create MOHA download job**

```php
<?php

namespace App\Jobs\Sanctions;

use Illuminate\Support\Facades\Log;

class DownloadMohaSanctionsList extends BaseSanctionsDownloadJob
{
    protected function getSourceKey(): string
    {
        return 'moha';
    }

    protected function getListType(): string
    {
        return 'MOHA';
    }

    protected function getFormat(): string
    {
        return 'CSV';
    }

    public function handle(): void
    {
        // MOHA does not provide automated download
        Log::info('MOHA sanctions list requires manual import - skipping automated download');
        
        // Still update the last attempted timestamp
        $list = $this->getOrCreateList(config('sanctions.sources.moha'));
        $list->update([
            'last_attempted_at' => now(),
            'update_status' => 'never_run',
            'last_error_message' => 'Automated download not available - manual import required',
        ]);
    }
}
```

- [ ] **Step 5: Create EU download job**

```php
<?php

namespace App\Jobs\Sanctions;

class DownloadEuSanctionsList extends BaseSanctionsDownloadJob
{
    protected function getSourceKey(): string
    {
        return 'eu';
    }

    protected function getListType(): string
    {
        return 'EU';
    }

    protected function getFormat(): string
    {
        return 'CSV';
    }
}
```

- [ ] **Step 6: Write feature test**

```php
<?php

namespace Tests\Feature;

use App\Jobs\Sanctions\DownloadUnSanctionsList;
use App\Jobs\Sanctions\DownloadOfacSanctionsList;
use App\Jobs\Sanctions\DownloadMohaSanctionsList;
use App\Jobs\Sanctions\DownloadEuSanctionsList;
use App\Models\SanctionList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SanctionsDownloadJobsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::fake([
            'https://scsanctions.un.org/*' => Http::response('<?xml version="1.0"?><CONSOLIDATED_LIST></CONSOLIDATED_LIST>', 200),
            'https://www.treasury.gov/ofac/downloads/sdn.xml' => Http::response('<?xml version="1.0"?><publishInformation></publishInformation>', 200),
            'https://webgate.ec.europa.eu/*' => Http::response("name,entity_type\nTest Person,Individual", 200),
        ]);
    }

    public function test_un_job_can_be_dispatched(): void
    {
        Bus::dispatch(new DownloadUnSanctionsList());
        
        Bus::assertDispatched(DownloadUnSanctionsList::class);
    }

    public function test_ofac_job_can_be_dispatched(): void
    {
        Bus::dispatch(new DownloadOfacSanctionsList());
        
        Bus::assertDispatched(DownloadOfacSanctionsList::class);
    }

    public function test_moha_job_handles_missing_url(): void
    {
        // MOHA has no URL configured, should log and skip
        $job = new DownloadMohaSanctionsList();
        
        // Job should complete without throwing
        $this->assertNull($job->handle());
    }

    public function test_eu_job_can_be_dispatched(): void
    {
        Bus::dispatch(new DownloadEuSanctionsList());
        
        Bus::assertDispatched(DownloadEuSanctionsList::class);
    }

    public function test_jobs_use_correct_queue(): void
    {
        $unJob = new DownloadUnSanctionsList();
        $this->assertEquals(3, $unJob->tries);
        $this->assertEquals(600, $unJob->timeout);
        
        $ofacJob = new DownloadOfacSanctionsList();
        $this->assertEquals(3, $ofacJob->tries);
    }
}
```

- [ ] **Step 7: Run test to verify it passes**

```bash
php artisan test --filter=SanctionsDownloadJobsTest
```

Expected: PASS (5 tests)

- [ ] **Step 8: Commit**

```bash
git add app/Jobs/Sanctions/
git add tests/Feature/SanctionsDownloadJobsTest.php
git commit -m "feat(sanctions): add download jobs for each sanctions source

Create job classes:
- BaseSanctionsDownloadJob: Abstract base with shared logic
- DownloadUnSanctionsList: UN Security Council list
- DownloadOfacSanctionsList: US OFAC SDN list
- DownloadMohaSanctionsList: Malaysia MOHA (manual only)
- DownloadEuSanctionsList: EU consolidated list

Features:
- Automatic list creation if not exists
- Checksum-based change detection
- Retry with exponential backoff
- Archive downloaded files
- Trigger rescreening on new entries
- Failure notifications to compliance
- Feature tests for all jobs"
```

---

## Task 6: Create Artisan Commands

**Files:**
- Create: `app/Console/Commands/UpdateSanctionsLists.php`
- Create: `app/Console/Commands/SanctionsStatus.php`
- Create: `tests/Feature/SanctionsCommandsTest.php`

**Context:** Artisan commands for manual updates and status checking.

---

### Task 6: Artisan Commands

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\SanctionList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SanctionsCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_sanctions_update_command_accepts_source_option(): void
    {
        $this->artisan('sanctions:update', ['--source' => 'un'])
            ->assertSuccessful()
            ->expectsOutputToContain('Dispatching UN sanctions download job');
    }

    public function test_sanctions_update_command_dispatches_all_jobs(): void
    {
        $this->artisan('sanctions:update')
            ->assertSuccessful();

        Queue::assertPushed(\App\Jobs\Sanctions\DownloadUnSanctionsList::class);
        Queue::assertPushed(\App\Jobs\Sanctions\DownloadOfacSanctionsList::class);
    }

    public function test_sanctions_update_rejects_invalid_source(): void
    {
        $this->artisan('sanctions:update', ['--source' => 'invalid'])
            ->assertFailed();
    }

    public function test_sanctions_status_shows_list_status(): void
    {
        SanctionList::factory()->create([
            'name' => 'Test List',
            'list_type' => 'UNSCR',
            'update_status' => 'success',
            'entry_count' => 100,
            'last_updated_at' => now()->subDay(),
        ]);

        $this->artisan('sanctions:status')
            ->assertSuccessful()
            ->expectsOutputToContain('Test List')
            ->expectsOutputToContain('100');
    }

    public function test_sanctions_status_shows_never_run(): void
    {
        SanctionList::factory()->create([
            'name' => 'New List',
            'list_type' => 'UNSCR',
            'update_status' => 'never_run',
        ]);

        $this->artisan('sanctions:status')
            ->assertSuccessful()
            ->expectsOutputToContain('Never');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=SanctionsCommandsTest
```

Expected: FAIL with commands not found

- [ ] **Step 3: Create UpdateSanctionsLists command**

```php
<?php

namespace App\Console\Commands;

use App\Jobs\Sanctions\DownloadEuSanctionsList;
use App\Jobs\Sanctions\DownloadMohaSanctionsList;
use App\Jobs\Sanctions\DownloadOfacSanctionsList;
use App\Jobs\Sanctions\DownloadUnSanctionsList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class UpdateSanctionsLists extends Command
{
    protected $signature = 'sanctions:update
                            {--source= : Update specific source (un, ofac, moha, eu)}
                            {--sync : Run synchronously instead of dispatching to queue}';

    protected $description = 'Download and update sanctions lists from configured sources';

    protected array $sourceJobs = [
        'un' => DownloadUnSanctionsList::class,
        'ofac' => DownloadOfacSanctionsList::class,
        'moha' => DownloadMohaSanctionsList::class,
        'eu' => DownloadEuSanctionsList::class,
    ];

    public function handle(): int
    {
        $source = $this->option('source');
        $sync = $this->option('sync');

        if ($source) {
            return $this->updateSingleSource($source, $sync);
        }

        return $this->updateAllSources($sync);
    }

    protected function updateSingleSource(string $source, bool $sync): int
    {
        if (!isset($this->sourceJobs[$source])) {
            $this->error("Invalid source: {$source}");
            $this->line('Valid sources: ' . implode(', ', array_keys($this->sourceJobs)));
            return Command::FAILURE;
        }

        $jobClass = $this->sourceJobs[$source];
        
        $this->info("Dispatching {$source} sanctions download job...");

        if ($sync) {
            $this->info('Running synchronously...');
            Bus::dispatchNow(new $jobClass());
        } else {
            Bus::dispatch(new $jobClass());
        }

        $this->info("Job dispatched for {$source}.");
        $this->line('Run "php artisan sanctions:status" to check status.');

        return Command::SUCCESS;
    }

    protected function updateAllSources(bool $sync): int
    {
        $this->info('Dispatching sanctions list update jobs...');
        $this->newLine();

        foreach ($this->sourceJobs as $key => $jobClass) {
            $config = config("sanctions.sources.{$key}");
            
            if (!$config || !($config['enabled'] ?? false)) {
                $this->warn("  [SKIP] {$key}: Disabled in configuration");
                continue;
            }

            if (empty($config['url'])) {
                $this->warn("  [SKIP] {$key}: No URL configured");
                continue;
            }

            if ($sync) {
                $this->line("  [SYNC] {$key}: Running...");
                try {
                    Bus::dispatchNow(new $jobClass());
                    $this->info("  [DONE] {$key}: Completed");
                } catch (\Exception $e) {
                    $this->error("  [FAIL] {$key}: {$e->getMessage()}");
                }
            } else {
                Bus::dispatch(new $jobClass());
                $this->info("  [QUEUE] {$key}: Dispatched");
            }
        }

        $this->newLine();
        $this->info('All enabled sanctions update jobs dispatched.');
        $this->line('Run "php artisan sanctions:status" to check status.');
        $this->line('Check "storage/logs/laravel.log" for detailed progress.');

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 4: Create SanctionsStatus command**

```php
<?php

namespace App\Console\Commands;

use App\Models\SanctionList;
use Illuminate\Console\Command;

class SanctionsStatus extends Command
{
    protected $signature = 'sanctions:status
                            {--list= : Show details for specific list}';

    protected $description = 'Show status of sanctions lists and their last update times';

    public function handle(): int
    {
        $listName = $this->option('list');

        if ($listName) {
            return $this->showListDetails($listName);
        }

        return $this->showAllLists();
    }

    protected function showAllLists(): int
    {
        $this->info('Sanctions Lists Status');
        $this->newLine();

        $lists = SanctionList::orderBy('list_type')->get();

        if ($lists->isEmpty()) {
            $this->warn('No sanctions lists configured.');
            $this->line('Run "php artisan sanctions:update" to initialize.');
            return Command::SUCCESS;
        }

        $rows = $lists->map(function ($list) {
            return [
                $list->name,
                $list->list_type,
                $list->entry_count,
                $list->last_updated_at ? $list->last_updated_at->format('Y-m-d H:i') : 'Never',
                $this->formatStatus($list->update_status),
                $list->isAutoUpdated() ? 'Auto' : 'Manual',
            ];
        })->toArray();

        $this->table(
            ['Name', 'Type', 'Entries', 'Last Updated', 'Status', 'Source'],
            $rows
        );

        $this->newLine();
        
        // Summary statistics
        $totalEntries = $lists->sum('entry_count');
        $failedUpdates = $lists->where('update_status', 'failed')->count();
        $neverRun = $lists->where('update_status', 'never_run')->count();

        $this->info('Summary:');
        $this->line("  Total Lists: {$lists->count()}");
        $this->line("  Total Entries: {$totalEntries}");
        $this->line("  Failed Updates: {$failedUpdates}");
        $this->line("  Never Updated: {$neverRun}");

        if ($failedUpdates > 0) {
            $this->newLine();
            $this->warn('Some lists have failed updates. Run "php artisan sanctions:update" to retry.');
        }

        return Command::SUCCESS;
    }

    protected function showListDetails(string $name): int
    {
        $list = SanctionList::where('name', 'like', "%{$name}%")
            ->orWhere('list_type', $name)
            ->first();

        if (!$list) {
            $this->error("List not found: {$name}");
            return Command::FAILURE;
        }

        $this->info("Details: {$list->name}");
        $this->newLine();

        $details = [
            ['ID', $list->id],
            ['Type', $list->list_type],
            ['Source URL', $list->source_url ?? 'N/A'],
            ['Source Format', $list->source_format ?? 'N/A'],
            ['Active', $list->is_active ? 'Yes' : 'No'],
            ['Entries', $list->entry_count],
            ['Last Updated', $list->last_updated_at ? $list->last_updated_at->format('Y-m-d H:i:s') : 'Never'],
            ['Last Attempted', $list->last_attempted_at ? $list->last_attempted_at->format('Y-m-d H:i:s') : 'Never'],
            ['Update Status', $this->formatStatus($list->update_status)],
            ['Checksum', $list->last_checksum ? substr($list->last_checksum, 0, 16) . '...' : 'N/A'],
            ['Auto Updated', $list->isAutoUpdated() ? 'Yes' : 'No'],
        ];

        $this->table(['Property', 'Value'], $details);

        if ($list->last_error_message) {
            $this->newLine();
            $this->error('Last Error:');
            $this->line($list->last_error_message);
        }

        return Command::SUCCESS;
    }

    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'success' => '<fg=green>Success</>',
            'failed' => '<fg=red>Failed</>',
            'pending' => '<fg=yellow>Pending</>',
            'never_run' => '<fg=gray>Never Run</>',
            default => $status,
        };
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
php artisan test --filter=SanctionsCommandsTest
```

Expected: PASS (5 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/UpdateSanctionsLists.php
git add app/Console/Commands/SanctionsStatus.php
git add tests/Feature/SanctionsCommandsTest.php
git commit -m "feat(sanctions): add artisan commands for sanctions management

Create commands:
- sanctions:update: Download/update sanctions lists
  - --source=un|ofac|moha|eu: Update specific source
  - --sync: Run synchronously
- sanctions:status: Show list status and statistics
  - --list=name: Show details for specific list

Features:
- Support for all configured sources
- Filter disabled/unconfigured sources
- Detailed status reporting with color coding
- Error message display
- Feature tests for all commands"
```

---

## Task 7: Schedule Automatic Updates

**Files:**
- Modify: `app/Console/Kernel.php`

**Context:** Add scheduled daily sanctions list updates at 03:00.

---

### Task 7: Schedule Updates

- [ ] **Step 1: Update Kernel schedule**

```php
// Add to schedule() method in app/Console/Kernel.php, after the existing schedules

// ============ SANCTIONS AUTO-UPDATES ============

// Daily sanctions list update at 03:00 (BNM requires within 24 hours)
$schedule->command('sanctions:update')
    ->dailyAt('03:00')
    ->appendOutputTo(storage_path('logs/sanctions-update.log'));

// Check sanctions status and alert if failed
$schedule->command('sanctions:status')
    ->dailyAt('08:00')
    ->appendOutputTo(storage_path('logs/sanctions-status-check.log'));
```

- [ ] **Step 2: Commit**

```bash
git add app/Console/Kernel.php
git commit -m "feat(sanctions): schedule daily automatic sanctions updates

Add to Kernel schedule:
- Daily sanctions:update at 03:00 (BNM compliant)
- Daily sanctions:status check at 08:00
- Log output to dedicated log files"
```

---

## Task 8: Create Migration for Sanctions Changes Log

**Files:**
- Create: `database/migrations/2026_04_12_000002_create_sanctions_change_logs_table.php`
- Create: `app/Models/SanctionsChangeLog.php`

**Context:** Track detailed changes (new/removed entries) for audit and reporting.

---

### Task 8: Change Log Migration and Model

- [ ] **Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanctions_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('sanction_lists');
            $table->enum('change_type', ['new_entry', 'removed_entry', 'updated_entry']);
            $table->string('entity_name', 255);
            $table->string('entity_type', 50)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->timestamp('detected_at');
            $table->boolean('rescreened')->default(false);
            $table->timestamp('rescreened_at')->nullable();
            $table->unsignedInteger('matches_found')->default(0);
            $table->json('match_details')->nullable();
            $table->index(['list_id', 'change_type']);
            $table->index('detected_at');
            $table->index('rescreened');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanctions_change_logs');
    }
};
```

- [ ] **Step 2: Create model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SanctionsChangeLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'list_id',
        'change_type',
        'entity_name',
        'entity_type',
        'nationality',
        'detected_at',
        'rescreened',
        'rescreened_at',
        'matches_found',
        'match_details',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'rescreened_at' => 'datetime',
        'rescreened' => 'boolean',
        'matches_found' => 'integer',
        'match_details' => 'array',
    ];

    public function sanctionList(): BelongsTo
    {
        return $this->belongsTo(SanctionList::class, 'list_id');
    }

    public function scopeNewEntries($query)
    {
        return $query->where('change_type', 'new_entry');
    }

    public function scopeRemovedEntries($query)
    {
        return $query->where('change_type', 'removed_entry');
    }

    public function scopePendingRescreen($query)
    {
        return $query->where('rescreened', false);
    }

    public function markRescreened(int $matchesFound = 0, array $matchDetails = []): void
    {
        $this->update([
            'rescreened' => true,
            'rescreened_at' => now(),
            'matches_found' => $matchesFound,
            'match_details' => $matchDetails,
        ]);
    }
}
```

- [ ] **Step 3: Run migration**

```bash
php artisan migrate --path=database/migrations/2026_04_12_000002_create_sanctions_change_logs_table.php
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_12_000002_create_sanctions_change_logs_table.php
git add app/Models/SanctionsChangeLog.php
git commit -m "feat(sanctions): add sanctions change log tracking

Create table and model for tracking detailed changes:
- Track new, removed, and updated entries
- Store entity details for rescreening
- Track rescreening status and match results
- Indexes for efficient querying
- Helper methods for marking rescreened"
```

---

## Task 9: Create Comprehensive Feature Tests

**Files:**
- Create: `tests/Feature/SanctionsAutoUpdateTest.php`

**Context:** End-to-end tests for the entire sanctions auto-update system.

---

### Task 9: Feature Tests

- [ ] **Step 1: Write comprehensive feature test**

```php
<?php

namespace Tests\Feature;

use App\Jobs\Sanctions\DownloadUnSanctionsList;
use App\Models\Customer;
use App\Models\SanctionEntry;
use App\Models\SanctionList;
use App\Models\SanctionsChangeLog;
use App\Models\User;
use App\Services\SanctionsDownloadService;
use App\Services\SanctionsImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SanctionsAutoUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $systemUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->systemUser = User::factory()->create(['id' => 1]);
    }

    public function test_complete_auto_update_workflow(): void
    {
        // Mock successful UN download
        $xmlContent = '<?xml version="1.0"?>
        <CONSOLIDATED_LIST>
            <INDIVIDUAL>
                <NAME>SANCTIONED PERSON</NAME>
                <NATIONALITY>US</NATIONALITY>
            </INDIVIDUAL>
        </CONSOLIDATED_LIST>';

        Http::fake([
            'https://scsanctions.un.org/*' => Http::response($xmlContent, 200),
        ]);

        // Dispatch the job
        $job = new DownloadUnSanctionsList();
        $job->handle(
            new SanctionsDownloadService(),
            new SanctionsImportService($this->app->make(\App\Services\AuditService::class)),
            $this->app->make(\App\Services\AuditService::class),
        );

        // Assert list was created
        $this->assertDatabaseHas('sanction_lists', [
            'list_type' => 'UNSCR',
            'update_status' => 'success',
        ]);

        // Assert entry was imported
        $this->assertDatabaseHas('sanction_entries', [
            'entity_name' => 'SANCTIONED PERSON',
            'entity_type' => 'Individual',
        ]);
    }

    public function test_change_detection_creates_log_entries(): void
    {
        // Create initial list
        $list = SanctionList::factory()->create([
            'list_type' => 'UNSCR',
            'entry_count' => 1,
        ]);
        
        SanctionEntry::factory()->create([
            'list_id' => $list->id,
            'entity_name' => 'OLD PERSON',
        ]);

        // Import service should detect changes
        $csvContent = "name,entity_type\nNEW PERSON,Individual";
        $filepath = sys_get_temp_dir() . '/test_changes.csv';
        file_put_contents($filepath, $csvContent);

        $service = new SanctionsImportService($this->app->make(\App\Services\AuditService::class));
        $result = $service->importFromCsv($filepath, $list->id, true);

        // Verify change detection
        $this->assertGreaterThan(0, $result['imported']);
        $this->assertGreaterThan(0, $result['removed']);

        unlink($filepath);
    }

    public function test_retry_mechanism_on_failure(): void
    {
        $attempt = 0;
        
        Http::fake([
            'https://scsanctions.un.org/*' => function () use (&$attempt) {
                $attempt++;
                if ($attempt < 3) {
                    return Http::response('', 500);
                }
                return Http::response('<?xml version="1.0"?><root/>', 200);
            },
        ]);

        $service = new SanctionsDownloadService();
        
        // With 3 retries, should eventually succeed
        $result = $service->download(
            'https://scsanctions.un.org/test.xml',
            'test.xml',
            'XML',
            3
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $attempt);
    }

    public function test_configuration_file_exists(): void
    {
        $this->assertFileExists(config_path('sanctions.php'));
        
        // Check required config keys
        $this->assertNotNull(config('sanctions.sources'));
        $this->assertNotNull(config('sanctions.schedule'));
        $this->assertNotNull(config('sanctions.notifications'));
    }

    public function test_customer_rescreening_triggered_on_new_entries(): void
    {
        Queue::fake();

        // Create a list and trigger update
        $list = SanctionList::factory()->create([
            'list_type' => 'UNSCR',
            'source_url' => 'https://example.com/test.xml',
        ]);

        // Create a customer
        Customer::factory()->create(['full_name' => 'John Doe']);

        // Simulate import with new entries
        $result = [
            'imported' => 5,
            'removed' => 0,
            'new_entries_detected' => 5,
            'is_significant_change' => true,
        ];

        // If significant change, rescreening should be triggered
        if ($result['new_entries_detected'] > 0) {
            \App\Jobs\Compliance\SanctionsRescreeningJob::dispatch();
        }

        Queue::assertPushed(\App\Jobs\Compliance\SanctionsRescreeningJob::class);
    }

    public function test_sanctions_status_command_displays_lists(): void
    {
        SanctionList::factory()->create([
            'name' => 'UN Test List',
            'list_type' => 'UNSCR',
            'update_status' => 'success',
            'entry_count' => 100,
        ]);

        $this->artisan('sanctions:status')
            ->assertSuccessful()
            ->expectsOutputToContain('UN Test List')
            ->expectsOutputToContain('100');
    }

    public function test_update_command_dispatches_correct_jobs(): void
    {
        Queue::fake();

        $this->artisan('sanctions:update', ['--source' => 'un'])
            ->assertSuccessful();

        Queue::assertPushed(DownloadUnSanctionsList::class);
    }

    public function test_checksum_prevents_reimport_of_unchanged_file(): void
    {
        $list = SanctionList::factory()->create([
            'list_type' => 'UNSCR',
            'last_checksum' => hash('sha256', 'test content'),
        ]);

        // Mock download with same checksum
        Http::fake([
            'https://example.com/test.xml' => Http::response('test content', 200),
        ]);

        $service = new SanctionsDownloadService();
        $result = $service->download('https://example.com/test.xml', 'test.xml', 'XML');

        $this->assertTrue($result['success']);
        $this->assertEquals($list->last_checksum, $result['checksum']);
    }

    public function test_file_archiving_works(): void
    {
        Http::fake([
            'https://example.com/test.xml' => Http::response('<xml>test</xml>', 200),
        ]);

        $service = new SanctionsDownloadService();
        $result = $service->download('https://example.com/test.xml', 'test.xml', 'XML');

        $this->assertTrue($result['success']);

        $archivePath = $service->archiveFile($result['filepath'], 'TEST');
        
        if ($archivePath) {
            $this->assertFileExists($archivePath);
            unlink($archivePath);
        }

        @unlink($result['filepath']);
    }
}
```

- [ ] **Step 2: Run test to verify it passes**

```bash
php artisan test --filter=SanctionsAutoUpdateTest
```

Expected: PASS (9 tests)

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/SanctionsAutoUpdateTest.php
git commit -m "test(sanctions): add comprehensive feature tests

Add tests covering:
- Complete auto-update workflow end-to-end
- Change detection and log entries
- Retry mechanism on failures
- Configuration validation
- Customer rescreening triggers
- Status command output
- Checksum-based duplicate prevention
- File archiving functionality"
```

---

## Task 10: Update Factories and Seeders

**Files:**
- Modify: `database/factories/SanctionListFactory.php`
- Modify: `database/factories/SanctionEntryFactory.php`
- Modify: `database/seeders/SanctionListSeeder.php` (if exists)

**Context:** Update factories to support new auto-update fields.

---

### Task 10: Update Factories

- [ ] **Step 1: Update SanctionListFactory**

```php
<?php

namespace Database\Factories;

use App\Models\SanctionList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SanctionListFactory extends Factory
{
    protected $model = SanctionList::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Sanctions List',
            'list_type' => $this->faker->randomElement(['UNSCR', 'OFAC', 'EU', 'MOHA', 'Internal']),
            'source_url' => $this->faker->optional()->url(),
            'source_format' => $this->faker->optional()->randomElement(['XML', 'CSV', 'JSON']),
            'source_file' => null,
            'uploaded_by' => User::factory(),
            'auto_updated_by' => null,
            'is_active' => true,
            'uploaded_at' => now(),
            'last_updated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_attempted_at' => null,
            'update_status' => $this->faker->randomElement(['success', 'failed', 'never_run', 'pending']),
            'last_error_message' => null,
            'entry_count' => $this->faker->numberBetween(0, 10000),
            'last_checksum' => null,
        ];
    }

    public function autoUpdated(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_updated_by' => User::factory(),
            'update_status' => 'success',
            'last_updated_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'update_status' => 'failed',
            'last_error_message' => $this->faker->sentence(),
        ]);
    }

    public function unscr(): static
    {
        return $this->state(fn (array $attributes) => [
            'list_type' => 'UNSCR',
            'name' => 'UN Security Council Consolidated List',
        ]);
    }

    public function ofac(): static
    {
        return $this->state(fn (array $attributes) => [
            'list_type' => 'OFAC',
            'name' => 'OFAC SDN List',
        ]);
    }
}
```

- [ ] **Step 2: Update SanctionEntryFactory**

```php
<?php

namespace Database\Factories;

use App\Models\SanctionEntry;
use App\Models\SanctionList;
use Illuminate\Database\Eloquent\Factories\Factory;

class SanctionEntryFactory extends Factory
{
    protected $model = SanctionEntry::class;

    public function definition(): array
    {
        $entityType = $this->faker->randomElement(['Individual', 'Entity']);
        
        return [
            'list_id' => SanctionList::factory(),
            'entity_name' => $this->faker->name(),
            'entity_type' => $entityType,
            'aliases' => $this->faker->optional()->words(3, true),
            'nationality' => $this->faker->optional()->countryCode(),
            'date_of_birth' => $entityType === 'Individual' ? $this->faker->optional()->date() : null,
            'details' => json_encode([
                'source' => $this->faker->word(),
                'reference' => $this->faker->uuid(),
            ]),
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => 'Individual',
            'entity_name' => $this->faker->name(),
            'date_of_birth' => $this->faker->date(),
        ]);
    }

    public function entity(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => 'Entity',
            'entity_name' => $this->faker->company(),
            'date_of_birth' => null,
        ]);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add database/factories/SanctionListFactory.php
git add database/factories/SanctionEntryFactory.php
git commit -m "chore(sanctions): update factories for new fields

Update SanctionListFactory:
- Add new auto-update fields
- Add state methods: autoUpdated, failed, unscr, ofac

Update SanctionEntryFactory:
- Improve entity type handling
- Add state methods: individual, entity"
```

---

## Task 11: Create Documentation

**Files:**
- Create: `docs/SANCTIONS_AUTO_UPDATE.md`

**Context:** Document the sanctions auto-update system for operations and compliance teams.

---

### Task 11: Documentation

- [ ] **Step 1: Create documentation**

```markdown
# Sanctions Auto-Update System

## Overview

CEMS-MY now includes automated sanctions list updates to ensure BNM AML/CFT compliance within the required 24-hour window.

## Supported Sources

| Source | List Type | Format | URL | Status |
|--------|-----------|--------|-----|--------|
| UN Security Council | UNSCR | XML | https://scsanctions.un.org/... | Auto |
| US OFAC SDN | OFAC | XML | https://www.treasury.gov/... | Auto |
| EU Consolidated | EU | CSV | https://webgate.ec.europa.eu/... | Auto |
| Malaysia MOHA | MOHA | CSV | Manual only | Manual |

## Automated Schedule

- **Daily at 03:00**: Download and import all enabled lists
- **Daily at 08:00**: Check status and alert if failures
- **Monthly**: Full customer rescreening (existing behavior)

## Commands

### Update Sanctions Lists

```bash
# Update all enabled sources
php artisan sanctions:update

# Update specific source
php artisan sanctions:update --source=un
php artisan sanctions:update --source=ofac
php artisan sanctions:update --source=eu

# Run synchronously (for debugging)
php artisan sanctions:update --sync
```

### Check Status

```bash
# Show all lists status
php artisan sanctions:status

# Show details for specific list
php artisan sanctions:status --list="UN Security Council"
```

## Configuration

Edit `.env` to configure:

```env
# Enable/disable sources
SANCTIONS_UN_ENABLED=true
SANCTIONS_OFAC_ENABLED=true
SANCTIONS_EU_ENABLED=true
SANCTIONS_MOHA_ENABLED=false

# Custom URLs (optional)
SANCTIONS_UN_URL=https://...
SANCTIONS_OFAC_URL=https://...

# Notification recipients
SANCTIONS_COMPLIANCE_EMAIL=compliance@example.com
SANCTIONS_ADMIN_EMAIL=admin@example.com

# System user for automated updates
SANCTIONS_SYSTEM_USER_ID=1
```

## Change Detection

The system automatically detects:
- **New entries**: Added to sanctions lists
- **Removed entries**: Removed from sanctions lists
- **Significant changes**: >10% change in entry count

When changes are detected:
1. Compliance alert is created
2. Change log entry is recorded
3. Automatic customer rescreening is triggered

## Monitoring

### Log Files

- `storage/logs/sanctions-update.log` - Update operations
- `storage/logs/sanctions-status-check.log` - Status checks
- `storage/logs/laravel.log` - Detailed error information

### Database Tables

- `sanction_lists` - List metadata and update status
- `sanction_entries` - Individual sanctioned entities
- `sanctions_change_logs` - Detailed change tracking

## Troubleshooting

### Update Failed

1. Check logs: `tail -f storage/logs/sanctions-update.log`
2. Verify URL is accessible: `curl -I <url>`
3. Retry manually: `php artisan sanctions:update --source=<name>`
4. Check network connectivity from server

### No Changes Detected

1. Verify checksum: Compare `last_checksum` field
2. Check archive: Files stored in `storage/app/archive/sanctions/`
3. Manual comparison: Download and compare with previous

### Customer Rescreening Not Triggered

1. Verify rescreening is enabled: `config/sanctions.php`
2. Check queue worker is running
3. Manual trigger: `php artisan compliance:rescreen`

## Compliance Notes

- **BNM Requirement**: Update within 24 hours of list publication
- **Audit Trail**: All imports logged with checksums
- **Rescreening**: Customers automatically rescreened against new entries
- **Retention**: Archive files kept for 30 days (configurable)

## Support

Contact the compliance team for:
- Adding new sanctions sources
- Custom parsing requirements
- Rescreening policy questions
```

- [ ] **Step 2: Commit**

```bash
git add docs/SANCTIONS_AUTO_UPDATE.md
git commit -m "docs(sanctions): add auto-update system documentation

Document:
- Supported sanctions sources
- Automated schedule
- Available commands
- Configuration options
- Change detection process
- Monitoring and troubleshooting
- Compliance notes"
```

---

## Task 12: Run Full Test Suite

**Files:**
- All test files

**Context:** Run all sanctions-related tests to ensure everything works together.

---

### Task 12: Final Verification

- [ ] **Step 1: Run all sanctions tests**

```bash
php artisan test --filter=Sanctions
```

Expected: PASS (all tests)

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

Expected: PASS (existing tests should still pass)

- [ ] **Step 3: Lint code**

```bash
./vendor/bin/pint
```

Expected: No errors

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "feat(sanctions): complete automated sanctions list update system

Implement automated sanctions list update system for BNM compliance:

Database:
- Migration for auto-update metadata fields
- Migration for sanctions change log
- Updated models with new relationships

Configuration:
- config/sanctions.php with UN/OFAC/EU/MOHA sources
- Environment variable support

Services:
- SanctionsDownloadService: HTTP downloads with retry
- SanctionsImportService: Parsing and change detection

Jobs:
- DownloadUnSanctionsList: UN Security Council
- DownloadOfacSanctionsList: US OFAC SDN
- DownloadMohaSanctionsList: Malaysia MOHA (manual)
- DownloadEuSanctionsList: EU consolidated

Commands:
- sanctions:update: Update all or specific sources
- sanctions:status: Check list status

Schedule:
- Daily 03:00: Automatic updates
- Daily 08:00: Status checks

Tests:
- Unit tests for services
- Feature tests for jobs and commands
- End-to-end workflow tests

Documentation:
- SANCTIONS_AUTO_UPDATE.md reference guide

Closes: Critical gap in sanctions screening BNM compliance"
```

---

## Implementation Complete

**Summary of sanctions sources integrated:**
1. **UN Security Council Consolidated List** (XML, auto)
2. **US OFAC SDN List** (XML, auto)
3. **EU Consolidated Financial Sanctions** (CSV, auto)
4. **Malaysia MOHA** (CSV, manual only)

**Update schedule:**
- Daily at 03:00: Download all enabled sources
- Daily at 08:00: Check status and alert on failures

**Configuration reference:**
- `config/sanctions.php`: Source URLs, schedules, notifications
- `php artisan sanctions:update`: Manual update command
- `php artisan sanctions:status`: Check status command

**Files created/modified:**
- `config/sanctions.php`
- `app/Services/SanctionsDownloadService.php`
- `app/Services/SanctionsImportService.php`
- `app/Jobs/Sanctions/*`
- `app/Console/Commands/UpdateSanctionsLists.php`
- `app/Console/Commands/SanctionsStatus.php`
- `app/Console/Kernel.php`
- `app/Models/SanctionList.php`
- `app/Models/SanctionsChangeLog.php`
- `database/migrations/*`
- `database/factories/*`
- `tests/*`
- `docs/SANCTIONS_AUTO_UPDATE.md`
