<?php

namespace Modules\Media\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        'metadata',
        'visibility',
    ];

    protected $casts = [
        'metadata' => 'json',
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

            $file->type = self::detectType($file->mime_type);
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

    public function getHumanSizeAttribute(): string
    {
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
    }

    private static function detectType(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            str_starts_with($mimeType, 'application/pdf') => 'pdf',
            str_starts_with($mimeType, 'text/') => 'document',
            str_starts_with($mimeType, 'application/') => 'document',
            default => 'unknown',
        };
    }
}
