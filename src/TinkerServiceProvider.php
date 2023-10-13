<?php

namespace QuantaQuirk\Tinker;

use QuantaQuirk\Contracts\Support\DeferrableProvider;
use QuantaQuirk\Foundation\Application as QuantaQuirkApplication;
use QuantaQuirk\Support\ServiceProvider;
use QuantaQuirk\Lumen\Application as LumenApplication;
use QuantaQuirk\Tinker\Console\TinkerCommand;

class TinkerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $source = realpath($raw = __DIR__.'/../config/tinker.php') ?: $raw;

        if ($this->app instanceof QuantaQuirkApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => $this->app->configPath('tinker.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('tinker');
        }

        $this->mergeConfigFrom($source, 'tinker');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.tinker', function () {
            return new TinkerCommand;
        });

        $this->commands(['command.tinker']);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.tinker'];
    }
}
