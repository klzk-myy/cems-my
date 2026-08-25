<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class RevaluationCompleteNotification extends Notification
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $results
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        $mailable = (new Mailable)
            ->subject('Monthly Revaluation Complete - '.now()->format('F Y'))
            ->view('emails.revaluation-complete', [
                'results' => $this->results,
            ]);

        if (! empty($this->results['report_path'])) {
            $mailable->attach($this->results['report_path']);
        }

        return $mailable;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'revaluation_complete',
            'positions_updated' => $this->results['positions_updated'] ?? 0,
            'report_path' => $this->results['report_path'] ?? null,
            'month' => now()->format('F Y'),
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'revaluation_complete';
    }
}
