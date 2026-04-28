<?php

namespace Illuminate\Contracts\JsonSchema;

use Closure;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;
use Illuminate\JsonSchema\Types\Type;

interface JsonSchema
{
    /**
     * Create a new object schema instance.
     *
     * @param  (Closure(JsonSchema): array<string, Type>)|array<string, Type>  $properties
     * @return ObjectType
     */
    public function object(Closure|array $properties = []);

    /**
     * Create a new array property instance.
     *
     * @return ArrayType
     */
    public function array();

    /**
     * Create a new string property instance.
     *
     * @return StringType
     */
    public function string();

    /**
     * Create a new integer property instance.
     *
     * @return IntegerType
     */
    public function integer();

    /**
     * Create a new number property instance.
     *
     * @return NumberType
     */
    public function number();

    /**
     * Create a new boolean property instance.
     *
     * @return BooleanType
     */
    public function boolean();
}
