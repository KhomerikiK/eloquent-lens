<?php

namespace Some\Other\Namespace;

use Illuminate\Database\Eloquent\Model;

class OutsideNamespace extends Model
{
    protected $fillable = ['name'];
}
