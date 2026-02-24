# EloquentLens

A dev tool for Laravel that gives you a visual overview of your Eloquent models.

It scans your model files using PHP reflection (no database queries), and renders an interactive board where you can see how everything connects.

> This is a **local development** package. Install it with `--dev`. Not meant for non-local environments.

## Install

```bash
composer require khomerikik/eloquent-lens --dev
php artisan eloquent-lens:install
```

Then open `/eloquent-lens` in your browser.

## What you get

**Board** — All your models laid out as cards, grouped by relationships. Drag, zoom, click around.

**Detail panel** — Click a model to see everything about it:
- Fillable, guarded, hidden fields
- Casts, accessors, mutators
- Relationships (type, target, pivot)
- Traits, scopes, global scopes
- Observers and event hooks
- Custom methods
- Policy detection
- Complexity score

**Path finder** — Pick two models and see how they connect through relationships.

**Sidebar** — Search and jump to any model.

Everything is read from your model classes. No database connection needed.

## Config

Published to `config/eloquent-lens.php`:

```php
'path' => 'eloquent-lens',          // URL prefix
'middleware' => ['web'],             // add 'auth' if needed
'model_paths' => [app_path('Models')],
'model_namespace' => 'App\\Models',
'excluded_models' => [],
'enabled' => env('ELOQUENT_LENS_ENABLED', true),
```

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- Livewire 3.x

## License

MIT
