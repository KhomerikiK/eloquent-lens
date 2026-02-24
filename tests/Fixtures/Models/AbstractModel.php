<?php

namespace EloquentLens\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{
    protected $fillable = ['name'];
}
