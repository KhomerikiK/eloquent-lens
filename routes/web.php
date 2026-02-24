<?php

use EloquentLens\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('eloquent-lens.path', 'eloquent-lens'),
    'middleware' => config('eloquent-lens.middleware', ['web']),
    'as' => 'eloquent-lens.',
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/models', [DashboardController::class, 'models'])->name('api.models');
});
