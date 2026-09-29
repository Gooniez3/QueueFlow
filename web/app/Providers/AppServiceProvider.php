<?php

namespace App\Providers;

use App\Contracts\QueuePresentationSource;
use App\Presentation\MockQueuePresentationSource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(QueuePresentationSource::class, MockQueuePresentationSource::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
