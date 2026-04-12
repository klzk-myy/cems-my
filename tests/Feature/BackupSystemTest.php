<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Backup System Test Suite
 * Tests backup creation, verification, cleanup, and health monitoring
 */
class BackupSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->backupService = app(BackupService::class);

        // Set up fake storage for testing
        Storage::fake('local');
        Storage::fake('s3');
    }

    // ============================================================
    // Backup Model Tests
    // ============================================================

    public function test_backup_log_can_be_created(): void
    {
        $log = BackupLog::create([
            'backup_name' => 'test-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_PENDING,
            'started_at' => now(),
        ]);

        $this->assertDatabaseHas('backup_logs', [
            'backup_name' => 'test-backup',
            'status' => BackupLog::STATUS_PENDING,
        ]);

        $this->assertEquals(BackupLog::TYPE_FULL, $log->backup_type);
        $this->assertEquals(BackupLog::DISK_LOCAL, $log->disk);
    }

    public function test_backup_log_mark_as_completed(): void
    {
        $log = BackupLog::create([
            'backup_name' => 'test-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_RUNNING,
            'started_at' => now()->subMinutes(5),
        ]);

        $log->markAsCompleted('/backups/test.zip', 1024000, 'abc123');

        $this->assertEquals(BackupLog::STATUS_COMPLETED, $log->fresh()->status);
        $this->assertEquals('/backups/test.zip', $log->file_path);
        $this->assertEquals(1024000, $log->file_size);
        $this->assertEquals('abc123', $log->checksum);
        $this->assertNotNull($log->completed_at);
    }

    public function test_backup_log_mark_as_failed(): void
    {
        $log = BackupLog::create([
            'backup_name' => 'test-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        $log->markAsFailed('Disk full');

        $this->assertEquals(BackupLog::STATUS_FAILED, $log->fresh()->status);
        $this->assertEquals('Disk full', $log->error_message);
        $this->assertNotNull($log->completed_at);
    }

    public function test_backup_log_mark_as_verified(): void
    {
        $log = BackupLog::create([
            'backup_name' => 'test-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(30),
        ]);

        $log->markAsVerified(true);

        $this->assertTrue($log->fresh()->verification_status);
        $this->assertEquals(BackupLog::STATUS_VERIFIED, $log->status);
        $this->assertNotNull($log->verified_at);
    }

    public function test_backup_log_is_successful_check(): void
    {
        $completed = BackupLog::create([
            'backup_name' => 'completed-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'started_at' => now(),
        ]);

        $verified = BackupLog::create([
            'backup_name' => 'verified-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_VERIFIED,
            'started_at' => now(),
        ]);

        $failed = BackupLog::create([
            'backup_name' => 'failed-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_FAILED,
            'started_at' => now(),
        ]);

        $this->assertTrue($completed->isSuccessful());
        $this->assertTrue($verified->isSuccessful());
        $this->assertFalse($failed->isSuccessful());
    }

    public function test_backup_log_is_verified_check(): void
    {
        $verified = BackupLog::create([
            'backup_name' => 'verified-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_VERIFIED,
            'verification_status' => true,
            'started_at' => now(),
        ]);

        $unverified = BackupLog::create([
            'backup_name' => 'unverified-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'verification_status' => null,
            'started_at' => now(),
        ]);

        $this->assertTrue($verified->isVerified());
        $this->assertFalse($unverified->isVerified());
    }

    public function test_backup_log_formatted_size(): void
    {
        $log = BackupLog::create([
            'backup_name' => 'test-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'file_size' => 1073741824, // 1 GB
            'started_at' => now(),
        ]);

        $this->assertEquals('1 GB', $log->formatted_size);
    }

    public function test_backup_log_duration_calculation(): void
    {
        $log = BackupLog::create([
            'backup_name' => 'test-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->assertNotNull($log->duration);
        $this->assertGreaterThanOrEqual(300, $log->duration); // At least 5 minutes
    }

    public function test_backup_log_scopes(): void
    {
        // Create backups of different types
        BackupLog::create([
            'backup_name' => 'db-backup',
            'backup_type' => BackupLog::TYPE_DATABASE,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'started_at' => now(),
        ]);

        BackupLog::create([
            'backup_name' => 's3-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_S3,
            'status' => BackupLog::STATUS_COMPLETED,
            'started_at' => now(),
        ]);

        BackupLog::create([
            'backup_name' => 'failed-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_FAILED,
            'started_at' => now(),
        ]);

        $this->assertEquals(2, BackupLog::completed()->count());
        $this->assertEquals(1, BackupLog::failed()->count());
        $this->assertEquals(1, BackupLog::byType(BackupLog::TYPE_DATABASE)->count());
        $this->assertEquals(1, BackupLog::byDisk(BackupLog::DISK_S3)->count());
    }

    public function test_backup_statistics(): void
    {
        // Create sample data
        BackupLog::create([
            'backup_name' => 'backup-1',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'file_size' => 1000000,
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addMinute(),
        ]);

        BackupLog::create([
            'backup_name' => 'backup-2',
            'backup_type' => BackupLog::TYPE_DATABASE,
            'disk' => BackupLog::DISK_S3,
            'status' => BackupLog::STATUS_FAILED,
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addMinute(),
        ]);

        $stats = BackupLog::getStatistics(7);

        $this->assertEquals(2, $stats['total_count']);
        $this->assertEquals(1, $stats['successful_count']);
        $this->assertEquals(1, $stats['failed_count']);
        $this->assertEquals(1000000, $stats['total_size']);
    }

    // ============================================================
    // Artisan Command Tests
    // ============================================================

    public function test_backup_list_command(): void
    {
        BackupLog::create([
            'backup_name' => 'test-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'file_size' => 1000000,
            'started_at' => now(),
        ]);

        $this->artisan('backup:list')
            ->assertSuccessful()
            ->expectsOutputToContain('test-backup');
    }

    public function test_backup_list_command_with_filters(): void
    {
        BackupLog::create([
            'backup_name' => 'db-backup',
            'backup_type' => BackupLog::TYPE_DATABASE,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'started_at' => now(),
        ]);

        BackupLog::create([
            'backup_name' => 's3-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_S3,
            'status' => BackupLog::STATUS_COMPLETED,
            'started_at' => now(),
        ]);

        $this->artisan('backup:list --disk=s3')
            ->assertSuccessful()
            ->expectsOutputToContain('s3-backup')
            ->doesntExpectOutputToContain('db-backup');
    }

    public function test_backup_monitor_command(): void
    {
        // Create a recent backup
        BackupLog::create([
            'backup_name' => 'recent-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'started_at' => now()->subHours(12),
            'completed_at' => now()->subHours(12),
        ]);

        // Command runs and outputs health check information
        // Note: Return code depends on overall health status
        $this->artisan('backup:monitor')
            ->expectsOutputToContain('Running backup health checks');
    }

    public function test_backup_verify_command_requires_valid_id(): void
    {
        $this->artisan('backup:verify 99999')
            ->assertFailed()
            ->expectsOutputToContain('Backup not found');
    }

    public function test_backup_restore_command_requires_valid_id(): void
    {
        $this->artisan('backup:restore 99999')
            ->assertFailed()
            ->expectsOutputToContain('Backup log with ID 99999 not found');
    }

    // ============================================================
    // Backup Service Tests
    // ============================================================

    public function test_backup_service_calculates_checksum(): void
    {
        // Use real storage path for checksum test
        $testPath = storage_path('app/test-backup-checksum.txt');
        $testContent = 'test content for checksum';
        file_put_contents($testPath, $testContent);

        $checksum = $this->backupService->calculateChecksum('test-backup-checksum.txt', 'local');

        // Cleanup
        if (file_exists($testPath)) {
            unlink($testPath);
        }

        $this->assertNotNull($checksum);
        $this->assertEquals(64, strlen($checksum)); // SHA-256 is 64 hex chars
        $this->assertEquals(hash('sha256', $testContent), $checksum);
    }

    public function test_backup_service_health_checks(): void
    {
        // Create a recent successful backup
        BackupLog::create([
            'backup_name' => 'healthy-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2),
        ]);

        $results = $this->backupService->runHealthChecks();

        $this->assertArrayHasKey('recent_backup', $results);
        $this->assertArrayHasKey('storage_space', $results);
        $this->assertArrayHasKey('backup_writable', $results);
        $this->assertArrayHasKey('recent_failures', $results);
        $this->assertArrayHasKey('verification_status', $results);
        $this->assertArrayHasKey('overall', $results);

        // Recent backup check should pass
        $this->assertTrue($results['recent_backup']['passed']);
        // Other checks may vary based on system state
        $this->assertIsBool($results['overall']['passed']);
    }

    public function test_backup_service_detects_missing_backup(): void
    {
        $results = $this->backupService->runHealthChecks();

        $this->assertFalse($results['recent_backup']['passed']);
        $this->assertStringContainsString('No successful backups', $results['recent_backup']['message']);
        $this->assertFalse($results['overall']['passed']);
    }

    // ============================================================
    // Configuration Tests
    // ============================================================

    public function test_backup_configuration_exists(): void
    {
        $this->assertFileExists(config_path('backup.php'));
        $config = config('backup');

        $this->assertNotNull($config);
        $this->assertArrayHasKey('backup', $config);
        $this->assertArrayHasKey('notifications', $config);
        $this->assertArrayHasKey('monitor_backups', $config);
        $this->assertArrayHasKey('cleanup', $config);
    }

    public function test_backup_configuration_has_encryption(): void
    {
        $config = config('backup.backup');

        $this->assertArrayHasKey('password', $config);
        $this->assertArrayHasKey('encryption', $config);
        $this->assertEquals('default', $config['encryption']);
    }

    public function test_backup_configuration_has_dual_storage(): void
    {
        $disks = config('backup.backup.destination.disks');

        $this->assertIsArray($disks);
        $this->assertContains('local', $disks);
        $this->assertContains('s3', $disks);
    }

    // ============================================================
    // Edge Cases & Error Handling
    // ============================================================

    public function test_backup_handles_invalid_type(): void
    {
        $this->artisan('backup:run --type=invalid')
            ->assertFailed()
            ->expectsOutputToContain('Invalid backup type');
    }

    public function test_backup_handles_invalid_disk(): void
    {
        $this->artisan('backup:run --disk=invalid')
            ->assertFailed()
            ->expectsOutputToContain('Invalid disk');
    }

    public function test_backup_log_handles_null_file_size(): void
    {
        $log = BackupLog::create([
            'backup_name' => 'test-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_COMPLETED,
            'file_size' => null,
            'started_at' => now(),
        ]);

        $this->assertEquals('N/A', $log->formatted_size);
    }

    public function test_backup_log_handles_null_duration(): void
    {
        $log = BackupLog::create([
            'backup_name' => 'test-backup',
            'backup_type' => BackupLog::TYPE_FULL,
            'disk' => BackupLog::DISK_LOCAL,
            'status' => BackupLog::STATUS_RUNNING,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $this->assertNull($log->duration);
    }
}
