<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Notification sent to admins when transactions are stuck in the
 * dead letter queue and need manual review.
 */
class DlqTransactionAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  Collection<int, Transaction>  $transactions
     */
    public function __construct(
        public Collection $transactions
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($this->shouldSendEmail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $count = $this->transactions->count();

        return (new MailMessage)
            ->subject("[DLQ] {$count} transaction(s) awaiting manual review - ".config('app.name'))
            ->error()
            ->line("{$count} transaction(s) are stuck in the dead letter queue and require manual review.")
            ->line('Transactions: '.$this->transactions->pluck('reference')->implode(', '))
            ->line('These transactions exhausted automatic retries. Review and retry them after resolving the underlying cause.')
            ->action('Review Dead Letter Queue', route('transactions.dlq'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'transaction_dlq',
            'count' => $this->transactions->count(),
            'transaction_ids' => $this->transactions->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'references' => $this->transactions->pluck('reference')->all(),
            'url' => route('transactions.dlq'),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'transaction_dlq',
            'data' => $this->toArray($notifiable),
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(User $notifiable): string
    {
        return 'transaction_dlq';
    }

    /**
     * Determine if email should be sent based on user preferences.
     */
    protected function shouldSendEmail(User $notifiable): bool
    {
        // Use the loaded relation collection when the command eager-loaded
        // preferences (avoids one query per admin); falls back to lazy loading.
        $preference = $notifiable->notificationPreferences
            ->firstWhere('notification_type', 'transaction_dlq');

        return $preference->email_enabled ?? true;
    }
}
