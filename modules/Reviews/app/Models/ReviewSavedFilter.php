<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Reviews\Database\Factories\ReviewSavedFilterFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReviewSavedFilter extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'review_saved_filters';

    protected static function newFactory()
    {
        return ReviewSavedFilterFactory::new();
    }

    protected $fillable = [
        'user_id',
        'name',
        'filters_json',
        'is_default',
        'is_shared',
        'shared_by',
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'is_default' => 'boolean',
            'is_shared' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'filters_json', 'is_default'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    public function scopeAvailableFor($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere(function ($q2) use ($userId) {
                    $q2->where('is_shared', true)->where('user_id', '!=', $userId);
                });
        });
    }

    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }

    public function setAsDefault(): bool
    {
        if ($this->is_default) {
            return true;
        }

        return $this->getConnection()->transaction(function () {
            static::where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);

            return $this->update(['is_default' => true]);
        });
    }

    public function hasFilter(string $key): bool
    {
        return isset($this->filters_json[$key]);
    }

    public function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters_json[$key] ?? $default;
    }

    public function applyToQuery($query)
    {
        $filters = $this->filters_json;

        if (isset($filters['star_rating'])) {
            $query->whereIn('star_rating', $filters['star_rating']);
        }

        if (isset($filters['has_comment'])) {
            $filters['has_comment']
                ? $query->whereNotNull('comment')
                : $query->whereNull('comment');
        }

        if (isset($filters['has_google_reply'])) {
            $filters['has_google_reply']
                ? $query->whereNotNull('google_reply_text')
                : $query->whereNull('google_reply_text');
        }

        if (isset($filters['has_our_reply'])) {
            $filters['has_our_reply']
                ? $query->has('replies')
                : $query->doesntHave('replies');
        }

        if (isset($filters['is_visible'])) {
            $query->whereHas('moderation', function ($q) use ($filters) {
                $q->where('is_visible', $filters['is_visible']);
            });
        }

        if (isset($filters['is_featured'])) {
            $query->whereHas('moderation', function ($q) use ($filters) {
                $q->where('is_featured', $filters['is_featured']);
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('review_time', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('review_time', '<=', $filters['date_to']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        $allowedColumns = ['review_time', 'star_rating', 'reviewer_name', 'location_id'];
        $allowedOrders = ['asc', 'desc'];

        if (
            isset($filters['sort_by'], $filters['sort_order']) &&
            in_array($filters['sort_by'], $allowedColumns, true) &&
            in_array(strtolower($filters['sort_order']), $allowedOrders, true)
        ) {
            $query->orderBy($filters['sort_by'], $filters['sort_order']);
        }

        return $query;
    }
}
