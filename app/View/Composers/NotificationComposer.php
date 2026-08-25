<?php

namespace App\View\Composers;

use App\Services\System\NotificationBadgeService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class NotificationComposer
{
    public function __construct(
        protected NotificationBadgeService $badgeService,
    ) {}

    /**
     * Bind the header bell data to the view.
     *
     * Composes only the app layout (not every view) so guests and pages that
     * never render the shell do not pay for the notification queries.
     */
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user) {
            $view->with('unreadNotifications', [])
                ->with('unreadNotificationCount', 0)
                ->with('headerDlqCount', 0);

            return;
        }

        try {
            $view->with('unreadNotifications', $this->badgeService->unreadList($user))
                ->with('unreadNotificationCount', $this->badgeService->unreadCount($user))
                ->with('headerDlqCount', $this->badgeService->dlqCount($user));
        } catch (\Exception $e) {
            Log::warning('NotificationComposer: Failed to fetch notification data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $view->with('unreadNotifications', [])
                ->with('unreadNotificationCount', 0)
                ->with('headerDlqCount', 0);
        }
    }
}
