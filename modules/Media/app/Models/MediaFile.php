<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MediaFile extends Model
{
    use SoftDeletes;

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
        'is_favorite',
    ];

    protected $casts = [
        'metadata' => 'json',
        'is_favorite' => 'boolean',
    ];

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
}
