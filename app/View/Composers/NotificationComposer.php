<?php

namespace App\View\Composers;

use App\Services\System\NotificationBadgeService;
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

        $view->with('unreadNotifications', $this->badgeService->unreadList($user))
            ->with('unreadNotificationCount', $this->badgeService->unreadCount($user))
            ->with('headerDlqCount', $this->badgeService->dlqCount($user));
    }
}
