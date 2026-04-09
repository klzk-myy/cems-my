<?php

namespace App\Providers;

use App\Events\TransactionCreated;
use App\Listeners\TransactionCreatedListener;
use App\Events\AlertCreated;
use App\Events\CaseOpened;
use App\Events\StrDraftGenerated;
use App\Events\RiskScoreUpdated;
use App\Events\ReportGenerated;
use App\Listeners\ComplianceEventListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TransactionCreated::class => [
            TransactionCreatedListener::class,
        ],
        AlertCreated::class => [
            ComplianceEventListener::class,
        ],
        CaseOpened::class => [
            ComplianceEventListener::class,
        ],
        StrDraftGenerated::class => [
            ComplianceEventListener::class,
        ],
        RiskScoreUpdated::class => [
            ComplianceEventListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
