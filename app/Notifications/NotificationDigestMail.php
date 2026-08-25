<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public int $totalCount,
        public array $byType,
        public string $period,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'CEMS-MY: Notification Digest',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification-digest',
        );
    }
}
