<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\System\NotificationBadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

/**
 * In-app notification actions for the header bell.
 *
 * Any authenticated user manages only their own notifications; the ownership
 * check on the single-read route prevents guessing another user's
 * notification id and acknowledging it on their behalf.
 */
class NotificationController extends Controller
{
    public function __construct(
        protected NotificationBadgeService $badgeService,
    ) {}

    /**
     * Mark every unread notification of the current user as read.
     */
    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark a single notification as read (own notifications only).
     */
    public function markRead(DatabaseNotification $notification): RedirectResponse
    {
        $this->assertOwnedByCurrentUser($notification);

        $notification->markAsRead();

        return back();
    }

    /**
     * Lightweight payload for the bell's live badge polling.
     *
     * dlq_count is only populated for admins (the badge service returns 0 for
     * everyone else) so branch-level staff never receive operational failure
     * counts.
     *
     * Response payload: { count: int, dlq_count: int }
     */
    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'count' => $user ? $this->badgeService->unreadCount($user) : 0,
            'dlq_count' => $this->badgeService->dlqCount($user),
        ]);
    }

    protected function assertOwnedByCurrentUser(DatabaseNotification $notification): void
    {
        if ($notification->notifiable_id !== auth()->id()
            || $notification->notifiable_type !== User::class) {
            abort(403, 'You can only manage your own notifications.');
        }
    }
}
