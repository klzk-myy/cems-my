<?php

namespace Tests\Unit;

use App\Enums\SystemAlertLevel;
use App\Models\SystemAlert;
use App\Models\User;
use App\Notifications\SystemHealthAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemHealthAlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function notificationFor(SystemAlertLevel $level): SystemHealthAlertNotification
    {
        $alert = SystemAlert::create([
            'level' => $level->value,
            'message' => 'Something needs attention',
            'source' => 'test',
        ]);

        return new SystemHealthAlertNotification($alert);
    }

    #[Test]
    public function critical_and_warning_alerts_include_mail_channel(): void
    {
        $user = User::factory()->create();

        $this->assertContains(
            'mail',
            $this->notificationFor(SystemAlertLevel::Critical)->via($user)
        );
        $this->assertContains(
            'mail',
            $this->notificationFor(SystemAlertLevel::Warning)->via($user)
        );
        $this->assertNotContains(
            'mail',
            $this->notificationFor(SystemAlertLevel::Info)->via($user)
        );
    }

    #[Test]
    public function to_mail_uses_correct_subject_prefix_and_renders(): void
    {
        $user = User::factory()->create();

        // Regression: this compared the enum to ->value strings (always false,
        // so every alert got an [INFO] subject), called ucfirst() on the enum
        // object (TypeError), and referenced a missing markdown view.
        $critical = $this->notificationFor(SystemAlertLevel::Critical)->toMail($user);
        $this->assertStringContainsString('[CRITICAL]', $critical->subject);
        $this->assertStringContainsString('Something needs attention', $critical->render());

        $warning = $this->notificationFor(SystemAlertLevel::Warning)->toMail($user);
        $this->assertStringContainsString('[WARNING]', $warning->subject);

        $info = $this->notificationFor(SystemAlertLevel::Info)->toMail($user);
        $this->assertStringContainsString('[INFO]', $info->subject);
    }

    #[Test]
    public function to_array_uses_string_level_values(): void
    {
        $user = User::factory()->create();
        $data = $this->notificationFor(SystemAlertLevel::Warning)->toArray($user);

        $this->assertSame('warning', $data['level']);
        $this->assertSame('Warning', $data['level_label']);
    }
}
