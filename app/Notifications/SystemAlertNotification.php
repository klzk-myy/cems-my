<?php

namespace App\Notifications;

use App\Enums\SystemAlertLevel;
use App\Models\SystemAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class SystemAlertNotification extends Notification
{
    use Queueable, SerializesModels;

    public function __construct(
        public SystemAlert $alert
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        $level = $this->alert->level;
        $prefix = match ($level) {
            SystemAlertLevel::Critical => '[CRITICAL]',
            SystemAlertLevel::Warning => '[WARNING]',
            default => '[INFO]',
        };
        $appName = config('app.name', 'CEMS-MY');

        return (new Mailable)
            ->subject("{$prefix} {$appName} System Alert")
            ->view('emails.system-alert', [
                'alert' => $this->alert,
                'prefix' => $prefix,
                'appName' => $appName,
            ])
            ->priority($level === SystemAlertLevel::Critical ? 1 : 3);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_alert',
            'alert_id' => $this->alert->id,
            'level' => $this->alert->level->value,
            'message' => $this->alert->message,
            'source' => $this->alert->source ?? null,
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'system_alert';
    }
}
