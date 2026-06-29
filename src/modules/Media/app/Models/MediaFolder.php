<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFolderFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MediaFolder extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uid',
        'name',
        'slug',
        'parent_id',
        'user_id',
        'color',
        'disk',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'parent_id', 'color', 'deleted_at'])
            ->logOnlyDirty()
            ->useLogName('media.folder');
    }

    protected static function booted(): void
    {
        static::creating(function (MediaFolder $folder): void {
            if (! $folder->uid) {
                $folder->uid = (string) Str::ulid();
            }

            if (! $folder->user_id) {
                $folder->user_id = auth()->id();
            }

            if (! $folder->slug) {
                $folder->slug = self::createSlug($folder->name, $folder->parent_id);
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
        return $this->hasMany(MediaFile::class, 'folder_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id')->withDefault();
    }

    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(MediaFavorite::class, 'favoritable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(MediaTag::class, 'taggable', 'media_taggables');
    }

    public function shares(): MorphMany
    {
        return $this->morphMany(MediaShare::class, 'shareable');
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

    public function scopeFavoritedBy(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= auth()->id();

        return $query->whereHas('favorites', fn ($q) => $q->where('user_id', $userId));
    }

    /**
     * Generate a unique slug for a folder within a parent.
     */
    public static function createSlug(string $name, int|string|null $parentId): string
    {
        $slug = Str::slug($name) ?: 'folder-'.time();
        $index = 1;
        $baseSlug = $slug;

        while (self::query()->where('slug', $slug)->where('parent_id', $parentId)->withTrashed()->exists()) {
            $slug = $baseSlug.'-'.$index++;
        }

        return $slug;
    }

    /**
     * Generate a unique name for a folder within a parent.
     */
    public static function createName(string $name, int|string|null $parentId): string
    {
        $index = 1;
        $baseName = $name;

        while (self::query()->where('name', $name)->where('parent_id', $parentId)->withTrashed()->exists()) {
            $name = $baseName.'-'.$index++;
        }

        return $name;
    }

    /**
     * Build the full path of a folder by traversing ancestors.
     */
    public static function getFullPath(int|string|null $folderId, ?string $path = ''): ?string
    {
        if (! $folderId) {
            return $path;
        }

        $folder = self::query()->where('id', $folderId)->withTrashed()->first();

        if (! $folder) {
            return $path;
        }

        $parent = self::getFullPath($folder->parent_id, $path);

        return $parent ? rtrim($parent, '/').'/'.$folder->slug : $folder->slug;
    }

    protected static function newFactory(): MediaFolderFactory
    {
        return MediaFolderFactory::new();
    }
}
