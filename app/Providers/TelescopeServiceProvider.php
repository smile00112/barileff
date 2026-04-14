<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Webkul\User\Models\Admin;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Configure the Telescope authorization services.
     */
    protected function authorization(): void
    {
        Telescope::auth(function ($request) {
            if (app()->environment('local')) {
                return true;
            }

            // Читаем напрямую из сессии, чтобы избежать проблем с Octane + guard caching
            $guardName = auth()->guard('admin')->getName();
            $adminId   = $request->session()->get($guardName);

            if (! $adminId) {
                return false;
            }

            $admin = \Webkul\User\Models\Admin::find($adminId);

            if (! $admin) {
                return false;
            }

            $allowedEmails = config('telescope.allowed_emails', []);

            return empty($allowedEmails) || in_array($admin->email, $allowedEmails);
        });
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user = null) {
            $guardName = auth()->guard('admin')->getName();
            $adminId   = request()->session()->get($guardName);

            if (! $adminId) {
                return false;
            }

            /** @var Admin|null $admin */
            $admin = \Webkul\User\Models\Admin::find($adminId);

            if (! $admin) {
                return false;
            }

            $allowedEmails = config('telescope.allowed_emails', []);

            return empty($allowedEmails) || in_array($admin->email, $allowedEmails);
        });
    }
}
