<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUid
{
    /**
     * Boot the HasUid trait
     */
    public static function bootHasUid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = Str::uuid();
            }
        });
    }
}
