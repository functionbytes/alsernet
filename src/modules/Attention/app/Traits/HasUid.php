<?php

namespace Modules\Attention\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Trait para gestión de identificadores únicos (UID)
 */
trait HasUid
{
    /**
     * Boot the trait
     */
    protected static function bootHasUid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    /**
     * Find model by UID
     *
     * @return static|null
     */
    public static function findByUid(string $uid): ?self
    {
        return static::where('uid', $uid)->first();
    }

    /**
     * Find model by UID or fail
     *
     * @return static
     *
     * @throws ModelNotFoundException
     */
    public static function findByUidOrFail(string $uid): self
    {
        return static::where('uid', $uid)->firstOrFail();
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'uid';
    }
}
