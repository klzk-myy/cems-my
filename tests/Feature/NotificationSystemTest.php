<?php

namespace Tests\Feature;

use App\Models\Compliance\ComplianceCase;
use App\Models\Customer;
use App\Models\DataBreachAlert;
use App\Models\FlaggedTransaction;
use App\Models\SanctionEntry;
use App\Models\StrReport;
use App\Models\SystemAlert;
use App\Models\Transaction;
use App\Models\TransactionConfirmation;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Notifications\ComplianceCaseAssignedNotification;
use App\Notifications\DataBreachAlertNotification;
use App\Notifications\LargeTransactionNotification;
use App\Notifications\SanctionsMatchNotification;
use App\Notifications\StrDeadlineApproachingNotification;
use App\Notifications\StrSubmissionFailedNotification;
use App\Notifications\SystemHealthAlertNotification;
use App\Notifications\TransactionFlaggedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $manager;

    protected User $complianceOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'email' => 'manager@example.com',
            'is_active' => true,
            'role' => \App\Enums\UserRole::Manager,
        ]);

        $this->complianceOfficer = User::factory()->create([
            'email' => 'compliance@example.com',
            'is_active' => true,
            'role' => \App\Enums\UserRole::ComplianceOfficer,
        ]);
    }

    /**
     * Test TransactionFlaggedNotification is sent correctly.
     */
    public function test_transaction_flagged_notification_is_sent(): void
    {
        Notification::fake();

        $flaggedTransaction = FlaggedTransaction::factory()->create([
            'transaction_id' => Transaction::factory()->create()->id,
            'customer_id' => Customer::factory()->create()->id,
            'flag_type' => \App\Enums\ComplianceFlagType::Velocity,
            'flag_reason' => 'Multiple transactions detected',
            'status' => \App\Enums\FlagStatus::Open,
        ]);

        $notification = new TransactionFlaggedNotification($flaggedTransaction);
        $this->complianceOfficer->notify($notification);

        Notification::assertSentTo(
            $this->complianceOfficer,
            TransactionFlaggedNotification::class,
            function ($notification, $channels) {
                return in_array('database', $channels) && in_array('broadcast', $channels);
            }
        );
    }

    /**
     * Test TransactionFlaggedNotification contains correct data.
     */
    public function test_transaction_flagged_notification_has_correct_data(): void
    {
        $flaggedTransaction = FlaggedTransaction::factory()->create([
            'transaction_id' => Transaction::factory()->create()->id,
            'customer_id' => Customer::factory()->create()->id,
            'flag_type' => \App\Enums\ComplianceFlagType::Velocity,
            'flag_reason' => 'Multiple transactions detected',
        ]);

        $notification = new TransactionFlaggedNotification($flaggedTransaction);
        $data = $notification->toArray($this->complianceOfficer);

        $this->assertEquals('transaction_flagged', $data['type']);
        $this->assertEquals($flaggedTransaction->id, $data['flagged_transaction_id']);
        $this->assertEquals($flaggedTransaction->transaction_id, $data['transaction_id']);
        $this->assertEquals('Velocity', $data['flag_type']);
        $this->assertEquals('Multiple transactions detected', $data['flag_reason']);
        $this->assertArrayHasKey('url', $data);
    }

    /**
     * Test STR Deadline notification is sent.
     */
    public function test_str_deadline_notification_is_sent(): void
    {
        Notification::fake();

        $strReport = StrReport::factory()->create([
            'str_no' => 'STR-2024-00001',
            'filing_deadline' => now()->addDay(),
            'status' => \App\Enums\StrStatus::Draft,
        ]);

        $notification = new StrDeadlineApproachingNotification($strReport, 1);
        $this->complianceOfficer->notify($notification);

        Notification::assertSentTo(
            $this->complianceOfficer,
            StrDeadlineApproachingNotification::class
        );
    }

    /**
     * Test STR Deadline notification calculates severity correctly.
     */
    public function test_str_deadline_notification_calculates_severity(): void
    {
        // Test critical (overdue)
        $strReport = StrReport::factory()->create([
            'filing_deadline' => now()->subDay(),
        ]);
        $notification = new StrDeadlineApproachingNotification($strReport, -1);
        $data = $notification->toArray($this->complianceOfficer);
        $this->assertEquals('critical', $data['severity']);

        // Test warning (2 days remaining)
        $strReport = StrReport::factory()->create([
            'filing_deadline' => now()->addDays(2),
        ]);
        $notification = new StrDeadlineApproachingNotification($strReport, 2);
        $data = $notification->toArray($this->complianceOfficer);
        $this->assertEquals('warning', $data['severity']);

        // Test info (5 days remaining)
        $strReport = StrReport::factory()->create([
            'filing_deadline' => now()->addDays(5),
        ]);
        $notification = new StrDeadlineApproachingNotification($strReport, 5);
        $data = $notification->toArray($this->complianceOfficer);
        $this->assertEquals('info', $data['severity']);
    }

    /**
     * Test STR Submission Failed notification is sent.
     */
    public function test_str_submission_failed_notification_is_sent(): void
    {
        Notification::fake();

        $strReport = StrReport::factory()->create([
            'str_no' => 'STR-2024-00001',
            'status' => \App\Enums\StrStatus::Failed,
        ]);

        $notification = new StrSubmissionFailedNotification(
            $strReport,
            'Connection timeout',
            2
        );
        $this->complianceOfficer->notify($notification);

        Notification::assertSentTo(
            $this->complianceOfficer,
            StrSubmissionFailedNotification::class,
            function ($notification) {
                $data = $notification->toArray($this->complianceOfficer);

                return $data['error_message'] === 'Connection timeout' &&
                       $data['retry_count'] === 2;
            }
        );
    }

    /**
     * Test Compliance Case Assigned notification is sent.
     */
    public function test_compliance_case_assigned_notification_is_sent(): void
    {
        Notification::fake();

        $case = ComplianceCase::factory()->create([
            'case_number' => 'CASE-2024-00001',
            'case_type' => \App\Enums\ComplianceCaseType::Investigation,
            'severity' => \App\Enums\FindingSeverity::High,
            'priority' => \App\Enums\ComplianceCasePriority::High,
            'sla_deadline' => now()->addHours(48),
        ]);

        $notification = new ComplianceCaseAssignedNotification($case, $this->manager);
        $this->complianceOfficer->notify($notification);

        Notification::assertSentTo(
            $this->complianceOfficer,
            ComplianceCaseAssignedNotification::class
        );
    }

    /**
     * Test Data Breach Alert notification is sent.
     */
    public function test_data_breach_alert_notification_is_sent(): void
    {
        Notification::fake();

        $alert = DataBreachAlert::factory()->create([
            'alert_type' => 'Unauthorized',
            'severity' => 'Critical',
            'record_count' => 10,
        ]);

        $notification = new DataBreachAlertNotification($alert);
        $this->complianceOfficer->notify($notification);

        Notification::assertSentTo(
            $this->complianceOfficer,
            DataBreachAlertNotification::class
        );
    }

    /**
     * Test Large Transaction notification is sent.
     */
    public function test_large_transaction_notification_is_sent(): void
    {
        Notification::fake();

        $transaction = Transaction::factory()->create([
            'amount_local' => '75000.0000',
            'type' => \App\Enums\TransactionType::Buy,
        ]);

        $confirmation = TransactionConfirmation::factory()->create([
            'transaction_id' => $transaction->id,
        ]);

        $notification = new LargeTransactionNotification($transaction, $confirmation);
        $this->manager->notify($notification);

        Notification::assertSentTo(
            $this->manager,
            LargeTransactionNotification::class
        );
    }

    /**
     * Test Sanctions Match notification is sent.
     */
    public function test_sanctions_match_notification_is_sent(): void
    {
        Notification::fake();

        $entry = SanctionEntry::factory()->create();

        $notification = new SanctionsMatchNotification($entry, 'Exact name match');
        $this->complianceOfficer->notify($notification);

        Notification::assertSentTo(
            $this->complianceOfficer,
            SanctionsMatchNotification::class
        );
    }

    /**
     * Test System Health Alert notification is sent.
     */
    public function test_system_health_alert_notification_is_sent(): void
    {
        Notification::fake();

        $alert = SystemAlert::factory()->create([
            'level' => 'warning',
            'message' => 'Database connection pool nearing capacity',
            'source' => 'system_monitor',
        ]);

        $notification = new SystemHealthAlertNotification($alert);
        $this->complianceOfficer->notify($notification);

        Notification::assertSentTo(
            $this->complianceOfficer,
            SystemHealthAlertNotification::class
        );
    }

    /**
     * Test notification is stored in database.
     */
    public function test_notification_is_stored_in_database(): void
    {
        $flaggedTransaction = FlaggedTransaction::factory()->create();

        $notification = new TransactionFlaggedNotification($flaggedTransaction);
        $this->complianceOfficer->notify($notification);

        $this->assertDatabaseHas('notifications', [
            'type' => 'transaction_flagged',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->complianceOfficer->id,
        ]);
    }

    /**
     * Test notification can be marked as read.
     */
    public function test_notification_can_be_marked_as_read(): void
    {
        $flaggedTransaction = FlaggedTransaction::factory()->create();

        $notification = new TransactionFlaggedNotification($flaggedTransaction);
        $this->complianceOfficer->notify($notification);

        $dbNotification = DatabaseNotification::where('notifiable_id', $this->complianceOfficer->id)
            ->where('notifiable_type', User::class)
            ->first();

        $this->assertNotNull($dbNotification);
        $this->assertNull($dbNotification->read_at);

        // Mark as read
        $dbNotification->markAsRead();

        $this->assertNotNull($dbNotification->fresh()->read_at);
    }

    /**
     * Test notification preferences are created with defaults.
     */
    public function test_notification_preferences_created_with_defaults(): void
    {
        $preference = UserNotificationPreference::getDefaultPreferences()['transaction_flagged'];

        $this->assertTrue($preference['email_enabled']);
        $this->assertFalse($preference['sms_enabled']);
        $this->assertTrue($preference['in_app_enabled']);
        $this->assertFalse($preference['push_enabled']);
    }

    /**
     * Test user can get notification preference.
     */
    public function test_user_can_get_notification_preference(): void
    {
        $preference = $this->user->getNotificationPreference('transaction_flagged');

        $this->assertInstanceOf(UserNotificationPreference::class, $preference);
        $this->assertEquals($this->user->id, $preference->user_id);
        $this->assertEquals('transaction_flagged', $preference->notification_type);
    }

    /**
     * Test notification type labels.
     */
    public function test_notification_type_labels(): void
    {
        $types = UserNotificationPreference::getNotificationTypes();

        $this->assertArrayHasKey('transaction_flagged', $types);
        $this->assertArrayHasKey('str_deadline_approaching', $types);
        $this->assertArrayHasKey('str_submission_failed', $types);
        $this->assertArrayHasKey('compliance_case_assigned', $types);
        $this->assertArrayHasKey('data_breach_alert', $types);
        $this->assertArrayHasKey('large_transaction', $types);
        $this->assertArrayHasKey('sanctions_match', $types);
        $this->assertArrayHasKey('system_health_alert', $types);

        $this->assertEquals('Transaction Flagged', $types['transaction_flagged']);
    }

    /**
     * Test critical notifications default to all channels.
     */
    public function test_critical_notifications_default_to_all_channels(): void
    {
        $defaults = UserNotificationPreference::getDefaultPreferences();

        // Data breach should have all channels enabled
        $this->assertTrue($defaults['data_breach_alert']['email_enabled']);
        $this->assertTrue($defaults['data_breach_alert']['sms_enabled']);
        $this->assertTrue($defaults['data_breach_alert']['in_app_enabled']);
        $this->assertTrue($defaults['data_breach_alert']['push_enabled']);
    }

    /**
     * Test notification channels are returned correctly.
     */
    public function test_notification_channels_returned_correctly(): void
    {
        $flaggedTransaction = FlaggedTransaction::factory()->create();
        $notification = new TransactionFlaggedNotification($flaggedTransaction);

        $channels = $notification->via($this->complianceOfficer);

        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /**
     * Test notification via methods work correctly.
     */
    public function test_notification_via_methods(): void
    {
        $flaggedTransaction = FlaggedTransaction::factory()->create();
        $notification = new TransactionFlaggedNotification($flaggedTransaction);

        // Test toArray
        $array = $notification->toArray($this->complianceOfficer);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('url', $array);

        // Test toBroadcast
        $broadcast = $notification->toBroadcast($this->complianceOfficer);
        $this->assertInstanceOf(\Illuminate\Notifications\Messages\BroadcastMessage::class, $broadcast);
    }

    /**
     * Test notification mail is generated correctly.
     */
    public function test_notification_mail_is_generated(): void
    {
        $flaggedTransaction = FlaggedTransaction::factory()->create([
            'flag_reason' => 'Test reason',
        ]);
        $notification = new TransactionFlaggedNotification($flaggedTransaction);

        $mail = $notification->toMail($this->complianceOfficer);

        $this->assertInstanceOf(\Illuminate\Notifications\Messages\MailMessage::class, $mail);
        $this->assertStringContainsString('Transaction Flagged', $mail->subject);
    }

    /**
     * Test email template renders correctly.
     */
    public function test_email_template_renders(): void
    {
        $flaggedTransaction = FlaggedTransaction::factory()->create();
        $notification = new TransactionFlaggedNotification($flaggedTransaction);
        $data = $notification->toArray($this->complianceOfficer);

        $rendered = view('emails.transaction-flagged', [
            'notifiable' => $this->complianceOfficer,
            'flaggedTransaction' => $flaggedTransaction,
            'transaction' => $flaggedTransaction->transaction,
            'customer' => $flaggedTransaction->customer,
            'flaggedBy' => null,
            'flagType' => 'velocity',
            'flagReason' => 'Test',
            'url' => route('compliance.flags.resolve', $flaggedTransaction->id),
        ])->render();

        $this->assertStringContainsString('Transaction Flagged', $rendered);
        $this->assertStringContainsString('Test', $rendered);
    }

    /**
     * Test notification queue implementation.
     */
    public function test_notifications_are_queued(): void
    {
        $flaggedTransaction = FlaggedTransaction::factory()->create();
        $notification = new TransactionFlaggedNotification($flaggedTransaction);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
    }

    /**
     * Test notification digest command exists.
     */
    public function test_notification_digest_command_exists(): void
    {
        $this->artisan('notifications:send-digest')
            ->assertSuccessful();
    }

    /**
     * Test notification test command exists.
     */
    public function test_notification_test_command_exists(): void
    {
        // Command exists and can be called - may fail due to no DB connection in test env
        // but the command itself should be registered
        $this->artisan('notifications:test', ['type' => 'transaction_flagged', '--dry-run' => true])
            ->assertSuccessful();
    }
}
