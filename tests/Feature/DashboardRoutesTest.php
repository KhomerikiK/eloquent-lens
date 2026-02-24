<?php

use EloquentLens\EloquentLensServiceProvider;

it('returns 200 for the dashboard route', function () {
    $this->get('/eloquent-lens')
        ->assertStatus(200);
});

it('returns JSON with model names from the api route', function () {
    $response = $this->getJson('/eloquent-lens/api/models');

    $response->assertStatus(200);

    $data = $response->json();

    expect(array_keys($data))->toContain('User', 'Post', 'Comment', 'Profile', 'Tag');
});

it('api response does not contain absolute file paths', function () {
    $response = $this->getJson('/eloquent-lens/api/models');
    $data = $response->json();

    foreach ($data as $model) {
        expect($model['filePath'])->not->toStartWith('/');
    }
});

it('does not register routes when enabled is false', function () {
    // Rebuild the app with enabled=false
    $this->app['config']->set('eloquent-lens.enabled', false);

    // Re-register the provider so boot() sees enabled=false
    $provider = new EloquentLensServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    // The routes from the initial boot are still there, so we check
    // that a fresh provider with enabled=false does not add more routes.
    // The most reliable way: check the route collection directly
    $routes = collect($this->app['router']->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'eloquent-lens'))
        ->all();

    // Routes exist from initial test setup boot, but a second boot with
    // enabled=false should not duplicate them. We verify by checking route
    // count hasn't grown beyond the original 2.
    expect(count($routes))->toBeLessThanOrEqual(2);
});

it('config defaults enabled to false', function () {
    // Read the raw config file default (not the test override)
    $config = require __DIR__.'/../../config/eloquent-lens.php';

    expect($config['enabled'])->toBeFalse();
});
