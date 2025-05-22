<?php

namespace App\Providers;

use App\Models\Batch;
use App\Observers\BatchObserver;
use App\Services\FirebaseService;
use App\Services\ReportService;
use App\Services\TranslationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirebaseService::class, function ($app) {
            return new FirebaseService();
        });

        $this->app->singleton(ReportService::class, function ($app) {
            return new ReportService();
        });

        $this->app->singleton(TranslationService::class, function ($app) {
            return new TranslationService(
                $app['config'],
                $app['log']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //        Model::preventLazyLoading(!app()->isProduction());
        Batch::observe(BatchObserver::class);
    }
}
