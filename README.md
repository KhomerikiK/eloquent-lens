<p align="center">
  <img src="arts/image-2.png" width="700" alt="EloquentLens loading screen" />
</p>

<h1 align="center">EloquentLens</h1>

<p align="center">
  A visual dashboard for your Laravel Eloquent models.<br>
  Relationships, scopes, casts, policies, complexity — all at a glance. No database queries.
</p>

<p align="center">
  <a href="https://packagist.org/packages/khomerikik/eloquent-lens"><img src="https://img.shields.io/packagist/v/khomerikik/eloquent-lens.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/khomerikik/eloquent-lens"><img src="https://img.shields.io/packagist/dt/khomerikik/eloquent-lens.svg?style=flat-square" alt="Total Downloads"></a>
  <img src="https://img.shields.io/packagist/php-v/khomerikik/eloquent-lens.svg?style=flat-square" alt="PHP Version">
  <img src="https://img.shields.io/badge/Laravel-10%20|%2011%20|%2012-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel Version">
  <img src="https://img.shields.io/github/license/khomerikik/eloquent-lens?style=flat-square" alt="License">
</p>

---

## Install

```bash
composer require khomerikik/eloquent-lens --dev
php artisan eloquent-lens:install
```

Then open `/eloquent-lens` in your browser.

> **Dev only** — install with `--dev`. Not meant for production.

---

## The Board

All your models laid out as cards, connected by relationship lines. Drag, zoom, filter by type.

<p align="center">
  <img src="arts/image-3.png" alt="Full board with model cards and relationship lines" />
</p>

---

## Detail Panel

Click any model to open a side panel with four tabs:

<p align="center">
  <img src="arts/image-1.png" alt="Board with detail panel open" />
</p>

<details>
<summary><strong>Overview</strong> — traits, casts, accessors, fillable fields, complexity score</summary>
<br>
<p align="center">
  <img src="arts/image-5.png" width="400" alt="Overview tab" />
</p>
</details>

<details>
<summary><strong>Relations</strong> — every relationship with its type and target model</summary>
<br>
<p align="center">
  <img src="arts/image-6.png" width="400" alt="Relations tab" />
</p>
</details>

<details>
<summary><strong>Behavior</strong> — local scopes, global scopes, observers, custom methods</summary>
<br>
<p align="center">
  <img src="arts/image-7.png" width="400" alt="Behavior tab" />
</p>
</details>

---

## Path Finder

Pick two models and discover how they connect through relationships, up to 5 hops deep.

<p align="center">
  <img src="arts/image-4.png" alt="Path Finder showing routes between Balance and Transfer" />
</p>

---

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

---

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

## License

MIT
