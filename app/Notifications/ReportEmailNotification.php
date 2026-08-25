<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class ReportEmailNotification extends Notification
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subject,
        public string $filePath
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return (new Mailable)
            ->subject($this->subject)
            ->view('emails.report', [
                'subject' => $this->subject,
            ])
            ->attach($this->filePath);
    }
}
