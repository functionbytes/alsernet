<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Reviews\Enums\ReplyStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReviewReply extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'review_replies';

    protected $fillable = [
        'review_id',
        'reply_text',
        'status',
        'error_message',
        'error_count',
        'created_by',
        'approved_by',
        'approved_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReplyStatus::class,
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reply_text', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'review_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeStatus($query, ReplyStatus|string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', ReplyStatus::DRAFT);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ReplyStatus::APPROVED);
    }

    public function scopePublished($query)
    {
        return $query->where('status', ReplyStatus::PUBLISHED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', ReplyStatus::FAILED);
    }

    public function isDraft(): bool
    {
        return $this->status === ReplyStatus::DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->status === ReplyStatus::APPROVED;
    }

    public function isPublished(): bool
    {
        return $this->status === ReplyStatus::PUBLISHED;
    }

    public function isFailed(): bool
    {
        return $this->status === ReplyStatus::FAILED;
    }

    public function markAsApproved(User $user): void
    {
        $this->update([
            'status' => ReplyStatus::APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function markAsPublished(?User $user = null): void
    {
        $this->update([
            'status' => ReplyStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => ReplyStatus::FAILED,
            'error_message' => $message,
            'error_count' => ($this->error_count ?? 0) + 1,
        ]);
    }
}
