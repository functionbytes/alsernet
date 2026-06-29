<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewExportHistory extends Model
{
    protected $table = 'review_export_history';

    protected $fillable = [
        'user_id',
        'filters',
        'format',
        'status',
        'file_path',
        'file_name',
        'row_count',
        'error_message',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'row_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isDownloadable(): bool
    {
        return $this->status === 'completed' && ! $this->isExpired();
    }
}
