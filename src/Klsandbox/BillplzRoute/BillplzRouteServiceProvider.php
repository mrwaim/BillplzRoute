<?php

namespace Klsandbox\BillplzRoute;

use Illuminate\Support\ServiceProvider;
use Klsandbox\SiteConfig\Services\SiteConfig;

class BillplzRouteServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/../../../routes/routes.php';
        }

        $this->publishes([
            __DIR__ . '/../../../config/' => config_path(),
        ], 'config');

        $this->loadViewsFrom(__DIR__ . '/../../../views/', 'billplz-route');

        $this->publishes([
            __DIR__ . '/../../../views/' => base_path('resources/views/vendor/billplz-route'),
        ], 'views');

        $this->publishes([
            __DIR__ . '/../../../database/migrations/' => database_path('/migrations'),
        ], 'migrations');

        SiteConfig::macro('billplz_enabled', function () {
            return config('billplz.enabled');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
