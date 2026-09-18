<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFileFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MediaFile extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uid',
        'name',
        'mime_type',
        'type',
        'size',
        'url',
        'alt',
        'folder_id',
        'user_id',
        'disk',
        'file_hash',
        'metadata',
        'visibility',
        'expires_at',
        'workflow_status',
        'approved_by_user_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function isFavoritedBy(int $userId): bool
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'folder_id', 'disk', 'visibility', 'deleted_at'])
            ->logOnlyDirty()
            ->useLogName('media.file');
    }

    protected static function booted(): void
    {
        static::creating(function (MediaFile $file): void {
            if (! $file->uid) {
                $file->uid = (string) Str::ulid();
            }

            if (! $file->user_id) {
                $file->user_id = auth()->id();
            }

            if (! $file->type) {
                $file->type = self::detectType($file->mime_type);
            }
        });
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id')->withDefault();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(MediaFavorite::class, 'favoritable');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MediaFileVersion::class)->orderByDesc('version_number');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(MediaAccessLog::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(MediaTag::class, 'taggable', 'media_taggables');
    }

    public function shares(): MorphMany
    {
        return $this->morphMany(MediaShare::class, 'shareable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(MediaComment::class, 'commentable');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    protected function humanSize(): Attribute
    {
        return Attribute::get(function (): string {
            $bytes = $this->size;
            $units = ['B', 'KB', 'MB', 'GB'];
            $size = $bytes;

            foreach ($units as $unit) {
                if ($size < 1024) {
                    return round($size, 2).' '.$unit;
                }
                $size /= 1024;
            }

            return round($size, 2).' TB';
        });
    }

    public function scopeByUser(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= auth()->id();

        return $query->where('user_id', $userId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', 'public');
    }

    public function scopeFavoritedBy(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= auth()->id();

        return $query->whereHas('favorites', fn ($q) => $q->where('user_id', $userId));
    }

    /**
     * Generate a unique name for a file in a folder.
     */
    public static function createName(string $name, int|string|null $folder): string
    {
        $index = 1;
        $baseName = $name;

        while (self::query()->where('name', $name)->where('folder_id', $folder)->withTrashed()->exists()) {
            $name = $baseName.'-'.$index++;
        }

        return $name;
    }

    /**
     * Generate a unique slug for a file in a folder path.
     */
    public static function createSlug(string $name, string $extension, ?string $folderPath): string
    {
        $slug = Str::slug($name) ?: 'file-'.time();
        $index = 1;
        $baseSlug = $slug;

        while (! empty($folderPath) && file_exists(rtrim($folderPath, '/').'/'.$slug.'.'.$extension)) {
            $slug = $baseSlug.'-'.$index++;
        }

        return $slug.'.'.$extension;
    }

    private static function detectType(string $mimeType): string
    {
        $mimeTypes = config('media.mime_types', []);

        foreach ($mimeTypes as $type => $mimes) {
            if (in_array($mimeType, $mimes)) {
                return $type;
            }
        }

        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            $mimeType === 'application/pdf' => 'pdf',
            default => 'document',
        };
    }

    protected static function newFactory(): MediaFileFactory
    {
        return MediaFileFactory::new();
    }
}
