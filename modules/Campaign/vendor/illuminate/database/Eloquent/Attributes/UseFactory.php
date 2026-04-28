<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;

#[Attribute(Attribute::TARGET_CLASS)]
class UseFactory
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<Factory>  $factoryClass
     */
    public function __construct(public string $factoryClass) {}
}
