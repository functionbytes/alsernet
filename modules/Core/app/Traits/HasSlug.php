<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->{$model->getSlugSourceField()});
            }
        });

        static::updating(function (self $model): void {
            if ($model->isDirty($model->getSlugSourceField()) && empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->{$model->getSlugSourceField()});
            }
        });
    }

    public function getSlugSourceField(): string
    {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'name';
    }

    public static function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $slug = Str::slug($source);
        $original = $slug;
        $count = 1;

        while (static::slugExists($slug, $ignoreId)) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }

    protected static function slugExists(string $slug, ?int $ignoreId): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
