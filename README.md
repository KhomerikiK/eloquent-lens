# EloquentLens

A Horizon-like dashboard that visualizes your Laravel Eloquent models, relationships, scopes, observers, and more — in real time.

![EloquentLens Dashboard](https://via.placeholder.com/800x400/09090f/7c3aed?text=EloquentLens+Dashboard)

## Features

- **Interactive Model Graph** — Drag, zoom, and explore your models as a visual network
- **Relationship Mapping** — See hasMany, belongsTo, morphs, pivots, through relationships color-coded
- **Deep Model Inspection** — Click any model to see traits, casts, accessors, mutators, scopes, observers
- **Security Audit** — Fillable/guarded/hidden fields, policy detection, mass assignment warnings
- **Path Finder** — Find how any two models connect through relationship chains
- **Complexity Scoring** — Identify god models that need refactoring
- **Global Scope Alerts** — Spot hidden query behavior from global scopes
- **Zero Config** — Works out of the box by scanning your `app/Models` directory

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- Livewire 3.x

## Installation

```bash
composer require khomerikik/eloquent-lens --dev
```

```bash
php artisan eloquent-lens:install
```

Visit `http://your-app.test/eloquent-lens` to see the dashboard.

## Configuration

After installation, the config file is at `config/eloquent-lens.php`:

```php
return [
    // URL prefix for the dashboard
    'path' => 'eloquent-lens',

    // Middleware (add auth middleware for non-local environments)
    'middleware' => ['web'],

    // Directories to scan for models
    'model_paths' => [
        app_path('Models'),
    ],

    // Base namespace for models
    'model_namespace' => 'App\\Models',

    // Models to exclude from the visualizer
    'excluded_models' => [],

    // Enable/disable the dashboard
    'enabled' => env('ELOQUENT_LENS_ENABLED', true),
];
```

## What It Detects

### Per Model
| Feature | Detection Method |
|---------|-----------------|
| **Relationships** | Reflection + return type analysis |
| **Traits** | `class_uses_recursive()` |
| **Fillable / Guarded / Hidden** | Property inspection |
| **Casts** | `getCasts()` |
| **Accessors** | `get*Attribute` + `Attribute` return type |
| **Mutators** | `set*Attribute` |
| **Local Scopes** | `scope*` method detection |
| **Global Scopes** | Source code parsing for `addGlobalScope` |
| **Observers** | `observe()` calls + inline event hooks |
| **Policies** | Convention-based policy class discovery |
| **Database Columns** | `Schema::getColumnListing()` |
| **Timestamps** | `usesTimestamps()` |
| **Soft Deletes** | `SoftDeletes` trait detection |

### Complexity Score (0–100)
Models are scored based on:
- Number of relationships (×6)
- Global scopes (×8)
- Observers (×5)
- Local scopes (×3)
- Accessors/Mutators (×2)
- Traits (×2)
- Casts (×1)
- Lines of code (up to ×20)

## Securing the Dashboard

For non-local environments, add authentication middleware:

```php
// config/eloquent-lens.php
'middleware' => ['web', 'auth', 'can:viewEloquentLens'],
```

Define a gate in `AuthServiceProvider`:

```php
Gate::define('viewEloquentLens', function ($user) {
    return $user->isAdmin();
});
```

## Screenshots

### Model Graph
Interactive node graph showing all models and their relationships with color-coded edges.

### Detail Panel
Click any model to inspect traits, casts, scopes, observers, fillable fields, and more.

### Path Finder
Select two models and discover all possible relationship paths between them.

## License

MIT
