<?php

use EloquentLens\Services\ModelParser;

beforeEach(function () {
    $this->parser = new ModelParser(
        [__DIR__.'/../Fixtures/Models'],
        'EloquentLens\\Tests\\Fixtures\\Models',
    );
    $this->models = $this->parser->parse();
});

// --- Discovery ---

it('discovers the correct number of models, skipping abstract', function () {
    expect($this->models)->toHaveCount(5);
    expect(array_keys($this->models))->each->not->toBe('AbstractModel');
});

it('discovers all expected model names', function () {
    expect(array_keys($this->models))
        ->toContain('User', 'Post', 'Comment', 'Profile', 'Tag');
});

it('respects excluded_models config', function () {
    $parser = new ModelParser(
        [__DIR__.'/../Fixtures/Models'],
        'EloquentLens\\Tests\\Fixtures\\Models',
        ['EloquentLens\\Tests\\Fixtures\\Models\\Tag'],
    );

    $models = $parser->parse();

    expect($models)->toHaveCount(4);
    expect(array_keys($models))->not->toContain('Tag');
});

// --- Fillable / Guarded / Hidden ---

it('parses fillable correctly', function () {
    expect($this->models['User']['fillable'])->toBe(['name', 'email']);
    expect($this->models['Post']['fillable'])->toBe(['title', 'body', 'user_id']);
});

it('parses guarded correctly', function () {
    expect($this->models['Post']['guarded'])->toBe(['slug']);
    // User has default guarded (['*'])
    expect($this->models['User']['guarded'])->toBe(['*']);
});

it('parses hidden correctly', function () {
    expect($this->models['User']['hidden'])->toBe(['password', 'remember_token']);
    expect($this->models['Post']['hidden'])->toBe([]);
});

// --- Casts ---

it('parses casts reflecting model-defined only', function () {
    expect($this->models['User']['casts'])->toBe([
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ]);
    expect($this->models['Post']['casts'])->toBe([
        'published_at' => 'datetime',
        'metadata' => 'array',
    ]);
});

it('does not include auto-injected id cast', function () {
    expect($this->models['User']['casts'])->not->toHaveKey('id');
    expect($this->models['Post']['casts'])->not->toHaveKey('id');
});

// --- Traits ---

it('detects SoftDeletes trait on User', function () {
    expect($this->models['User']['traits'])->toContain('SoftDeletes');
});

it('does not detect SoftDeletes on Post', function () {
    expect($this->models['Post']['traits'])->not->toContain('SoftDeletes');
});

// --- Relationships ---

it('detects User relationships with correct types', function () {
    $rels = $this->models['User']['relationships'];

    expect($rels)->toHaveKeys(['posts', 'profile']);
    expect($rels['posts']['type'])->toBe('hasMany');
    expect($rels['posts']['model'])->toBe('Post');
    expect($rels['profile']['type'])->toBe('hasOne');
    expect($rels['profile']['model'])->toBe('Profile');
});

it('detects Post relationships with correct types', function () {
    $rels = $this->models['Post']['relationships'];

    expect($rels)->toHaveKeys(['user', 'comments', 'tags']);
    expect($rels['user']['type'])->toBe('belongsTo');
    expect($rels['user']['model'])->toBe('User');
    expect($rels['comments']['type'])->toBe('hasMany');
    expect($rels['comments']['model'])->toBe('Comment');
    expect($rels['tags']['type'])->toBe('belongsToMany');
    expect($rels['tags']['model'])->toBe('Tag');
});

it('detects Comment belongsTo relationships', function () {
    $rels = $this->models['Comment']['relationships'];

    expect($rels)->toHaveKeys(['post', 'user']);
    expect($rels['post']['type'])->toBe('belongsTo');
    expect($rels['user']['type'])->toBe('belongsTo');
});

it('detects Tag belongsToMany relationship', function () {
    $rels = $this->models['Tag']['relationships'];

    expect($rels)->toHaveKey('posts');
    expect($rels['posts']['type'])->toBe('belongsToMany');
    expect($rels['posts']['model'])->toBe('Post');
});

// --- Scopes ---

it('detects local scopes on Post', function () {
    expect($this->models['Post']['scopes'])->toContain('published', 'recent');
});

it('does not detect scopes on models without them', function () {
    expect($this->models['User']['scopes'])->toBe([]);
    expect($this->models['Comment']['scopes'])->toBe([]);
});

// --- Accessors ---

it('detects legacy accessors on User', function () {
    expect($this->models['User']['accessors'])->toContain('full_name');
});

// --- Custom Methods ---

it('detects custom methods on User', function () {
    $methodNames = array_column($this->models['User']['methods'], 'name');

    expect($methodNames)->toContain('markAsVerified');
});

it('excludes relationship methods from custom methods', function () {
    $methodNames = array_column($this->models['User']['methods'], 'name');

    expect($methodNames)->not->toContain('posts', 'profile');
});

it('excludes accessor methods from custom methods', function () {
    $methodNames = array_column($this->models['User']['methods'], 'name');

    expect($methodNames)->not->toContain('getFullNameAttribute');
});

it('excludes scope methods from custom methods', function () {
    $methodNames = array_column($this->models['Post']['methods'], 'name');

    expect($methodNames)->not->toContain('scopePublished', 'scopeRecent');
});

// --- Soft Deletes / Timestamps ---

it('correctly identifies soft deletes', function () {
    expect($this->models['User']['hasSoftDeletes'])->toBeTrue();
    expect($this->models['Post']['hasSoftDeletes'])->toBeFalse();
});

it('correctly identifies timestamps', function () {
    expect($this->models['User']['hasTimestamps'])->toBeTrue();
    expect($this->models['Post']['hasTimestamps'])->toBeTrue();
});

// --- Table Name ---

it('resolves table names correctly', function () {
    expect($this->models['User']['table'])->toBe('users');
    expect($this->models['Post']['table'])->toBe('posts');
    expect($this->models['Comment']['table'])->toBe('comments');
    expect($this->models['Profile']['table'])->toBe('profiles');
    expect($this->models['Tag']['table'])->toBe('tags');
});

// --- Complexity ---

it('calculates complexity score > 0 for non-trivial models', function () {
    expect($this->models['User']['complexity'])->toBeGreaterThan(0);
    expect($this->models['Post']['complexity'])->toBeGreaterThan(0);
});

it('caps complexity score at 100', function () {
    foreach ($this->models as $model) {
        expect($model['complexity'])->toBeLessThanOrEqual(100);
    }
});

// --- FQCN / Namespace ---

it('includes correct fqcn and namespace', function () {
    expect($this->models['User']['fqcn'])
        ->toBe('EloquentLens\\Tests\\Fixtures\\Models\\User');
    expect($this->models['User']['namespace'])
        ->toBe('EloquentLens\\Tests\\Fixtures\\Models');
});
