<?php

namespace Modules\Webhook\Traits;

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
                $model->uid = (string) Str::ulid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uid';
    }
}
