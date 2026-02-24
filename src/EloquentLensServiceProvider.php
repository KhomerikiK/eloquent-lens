<?php

namespace EloquentLens;

use EloquentLens\Console\InstallCommand;
use EloquentLens\Services\ModelParser;
use Illuminate\Support\ServiceProvider;

class EloquentLensServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eloquent-lens.php', 'eloquent-lens');

        $this->app->singleton(ModelParser::class, function ($app) {
            return new ModelParser(
                config('eloquent-lens.model_paths'),
                config('eloquent-lens.model_namespace'),
                config('eloquent-lens.excluded_models', [])
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/eloquent-lens.php' => config_path('eloquent-lens.php'),
            ], 'eloquent-lens-config');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/eloquent-lens'),
            ], 'eloquent-lens-assets');
        }

        if (! config('eloquent-lens.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'eloquent-lens');
    }
}
