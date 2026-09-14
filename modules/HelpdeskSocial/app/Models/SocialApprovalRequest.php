<?php

namespace Modules\HelpdeskSocial\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskSocial\Database\Factories\SocialApprovalRequestFactory;

class SocialApprovalRequest extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_approval_requests';

    protected $fillable = [
        'social_comment_id',
        'requested_by_user_id',
        'approver_user_id',
        'action_type',
        'payload',
        'status',
        'approver_note',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'responded_at' => 'datetime',
        ];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class, 'social_comment_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function approve(?string $note = null): void
    {
        $this->update([
            'status' => 'approved',
            'approver_note' => $note,
            'responded_at' => now(),
        ]);
    }

    public function reject(?string $note = null): void
    {
        $this->update([
            'status' => 'rejected',
            'approver_note' => $note,
            'responded_at' => now(),
        ]);
    }

    protected static function newFactory(): SocialApprovalRequestFactory
    {
        return SocialApprovalRequestFactory::new();
    }
}
