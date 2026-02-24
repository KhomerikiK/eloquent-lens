<?php

namespace EloquentLens\Tests;

use EloquentLens\EloquentLensServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            EloquentLensServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('eloquent-lens.model_paths', [
            __DIR__.'/Fixtures/Models',
        ]);
        $app['config']->set('eloquent-lens.model_namespace', 'EloquentLens\\Tests\\Fixtures\\Models');
        $app['config']->set('eloquent-lens.enabled', true);
    }
}
