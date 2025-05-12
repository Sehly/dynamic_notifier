<?php

namespace DynamicNotifier;

use Illuminate\Support\ServiceProvider;

class DynamicNotifierServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dynamic_notifier.php', 'dynamic_notifier');
        $this->app->singleton('dynamic.notifier', function () {
        return new \DynamicNotifier\Helpers\NotifierEngine();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/dynamic_notifier.php' => config_path('dynamic_notifier.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
