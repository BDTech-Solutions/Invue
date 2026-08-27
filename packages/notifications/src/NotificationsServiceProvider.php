<?php

namespace Invue\Notifications;

use Illuminate\Support\ServiceProvider;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Runs automatically with the consuming app's own `php artisan
        // migrate` — no publish step, since this table is required
        // infrastructure for ->sendToDatabase(), not app-customizable
        // schema the way a real config file would be.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
