<?php

namespace App\Providers;

use App\Models\Event;
use App\Observers\EventObserverForOccurrences;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::observe(EventObserverForOccurrences::class);
    }
}
