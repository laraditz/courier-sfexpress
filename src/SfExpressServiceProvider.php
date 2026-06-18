<?php

namespace Laraditz\Courier\SfExpress;

use Illuminate\Support\ServiceProvider;

class SfExpressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sfexpress.php', 'courier.drivers.sfexpress');
    }

    public function boot(): void
    {
        $this->app->make('courier')->extend(
            'sfexpress',
            fn ($app, $config) => new SfExpressDriver($config)
        );

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sfexpress.php' => config_path('courier.php'),
            ], 'courier-sfexpress-config');
        }
    }
}
