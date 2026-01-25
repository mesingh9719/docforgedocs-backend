<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Queue::failing(function (\Illuminate\Queue\Events\JobFailed $event) {
            \App\Services\ActivityLogger::log(
                'job.failed',
                'Job failed: ' . $event->job->resolveName(),
                'error',
                [
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                    'exception' => $event->exception->getMessage(),
                ]
            );
        });
    }
}
