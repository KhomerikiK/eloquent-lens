<?php

namespace EloquentLens\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelWithSideEffect extends Model
{
    protected $fillable = ['name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A public zero-arg method that should NOT be invoked during parsing.
     * If called, it sets a global flag we can detect in tests.
     */
    public function dangerousAction(): bool
    {
        $_ENV['ELOQUENT_LENS_SIDE_EFFECT_CALLED'] = true;

        return true;
    }
}
