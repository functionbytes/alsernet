<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReviewModeration extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'review_moderations';

    protected $fillable = [
        'review_id',
        'is_visible',
        'is_featured',
        'tags',
        'internal_notes',
        'moderated_by',
        'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'tags' => 'array',
            'moderated_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['is_visible', 'is_featured', 'tags'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'review_id');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function toggleVisibility(): void
    {
        $this->update(['is_visible' => ! $this->is_visible]);
    }

    public function toggleFeatured(): void
    {
        $this->update(['is_featured' => ! $this->is_featured]);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];

        if (! in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_values(array_diff($tags, [$tag]));
        $this->update(['tags' => $tags]);
    }
}
