<?php

return [

    /*
    |--------------------------------------------------------------------------
    | EloquentLens Route Prefix
    |--------------------------------------------------------------------------
    */
    'path' => 'eloquent-lens',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    | Middleware applied to EloquentLens routes. In production, you should
    | use authorization middleware to restrict access.
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Model Directories
    |--------------------------------------------------------------------------
    | Directories to scan for Eloquent models, relative to the base path.
    */
    'model_paths' => [
        app_path('Models'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Namespace
    |--------------------------------------------------------------------------
    */
    'model_namespace' => 'App\\Models',

    /*
    |--------------------------------------------------------------------------
    | Excluded Models
    |--------------------------------------------------------------------------
    */
    'excluded_models' => [],

    /*
    |--------------------------------------------------------------------------
    | Enabled in Environment
    |--------------------------------------------------------------------------
    | EloquentLens is designed for local development. It is disabled by
    | default — set ELOQUENT_LENS_ENABLED=true in your .env to enable it.
    */
    'enabled' => env('ELOQUENT_LENS_ENABLED', false),

];
