<?php

namespace EloquentLens;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use EloquentLens\Http\Livewire\ModelGraph;
use EloquentLens\Http\Livewire\ModelDetail;
use EloquentLens\Http\Livewire\PathFinder;
use EloquentLens\Http\Livewire\SearchBar;
use EloquentLens\Console\InstallCommand;
use EloquentLens\Services\ModelParser;

class EloquentLensServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/eloquent-lens.php', 'eloquent-lens');

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
        if (! config('eloquent-lens.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'eloquent-lens');

        // Register Livewire components
        Livewire::component('eloquent-lens::model-graph', ModelGraph::class);
        Livewire::component('eloquent-lens::model-detail', ModelDetail::class);
        Livewire::component('eloquent-lens::path-finder', PathFinder::class);
        Livewire::component('eloquent-lens::search-bar', SearchBar::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/eloquent-lens.php' => config_path('eloquent-lens.php'),
            ], 'eloquent-lens-config');

            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/eloquent-lens'),
            ], 'eloquent-lens-assets');
        }
    }
}
