<?php

namespace Webkul\ManagerApp\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Listeners registered in Tasks 5 and 10
    }
}
