<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Builder;

#[Attribute(Attribute::TARGET_CLASS)]
class UseEloquentBuilder
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<Builder>  $builderClass
     */
    public function __construct(public string $builderClass) {}
}
