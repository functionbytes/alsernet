<?php

namespace Illuminate\Contracts\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;

interface DeviatesCastableAttributes
{
    /**
     * Increment the attribute.
     *
     * @param  Model  $model
     * @param  mixed  $value
     * @return mixed
     */
    public function increment($model, string $key, $value, array $attributes);

    /**
     * Decrement the attribute.
     *
     * @param  Model  $model
     * @param  mixed  $value
     * @return mixed
     */
    public function decrement($model, string $key, $value, array $attributes);
}
