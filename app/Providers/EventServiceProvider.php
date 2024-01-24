<?php

namespace App\Providers;

use App\Events\InvoiceCreated;
use App\Events\JobEnded;
use App\Events\JobStarted;
use App\Listeners\LogInvoiceHistory;
use App\Listeners\LogJobEnded;
use App\Listeners\LogJobStarted;
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
        InvoiceCreated::class => [
            LogInvoiceHistory::class,
        ],
        JobStarted::class => [
            LogJobStarted::class
        ],
        JobEnded::class => [
            LogJobEnded::class
        ]
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
