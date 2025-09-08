<?php

namespace PayGate\LaravelPayGateGlobal\Providers;

use Illuminate\Support\ServiceProvider;
use PayGate\LaravelPayGateGlobal\Services\PayGateGlobalService;

class PayGateGlobalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/paygate-global.php',
            'paygate-global'
        );

        $this->app->singleton('paygate-global', function ($app) {
            return new PayGateGlobalService();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/paygate-global.php' => config_path('paygate-global.php'),
            ], 'paygate-global-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            ], 'paygate-global-migrations');
        }

        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
    }

    public function provides(): array
    {
        return ['paygate-global'];
    }
}