<?php

namespace Modules\Media\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MediaFolder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uid',
        'name',
        'slug',
        'parent_id',
        'user_id',
        'color',
        'disk',
    ];

    protected static function booted(): void
    {
        static::creating(function (MediaFolder $folder): void {
            if (! $folder->uid) {
                $folder->uid = (string) Str::ulid();
            }

            if (! $folder->slug) {
                $folder->slug = Str::slug($folder->name).'-'.Str::random(8);
            }

            if (! $folder->user_id) {
                $folder->user_id = auth()->id();
            }
        });

        static::deleting(function (MediaFolder $folder): void {
            if ($folder->isForceDeleting()) {
                $folder->files()->withTrashed()->each(fn (MediaFile $file) => $file->forceDelete());
            } else {
                $folder->files()->withTrashed()->each(fn (MediaFile $file) => $file->delete());
            }
        });

        static::restoring(function (MediaFolder $folder): void {
            $folder->files()->withTrashed()->each(fn (MediaFile $file) => $file->restore());
        });
    }

    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'folder_id', 'id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id')->withDefault();
    }

    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByUser(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= auth()->id();

        return $query->where('user_id', $userId);
    }
}
