<?php

namespace Modules\Chat\Models\Sla;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Database\Factories\SlaAppliedConversationFactory;
use Modules\Chat\Models\Conversations\Conversation;

class SlaAppliedConversation extends Model
{
    use HasFactory;

    protected $table = 'chat_sla_applied_conversations';

    protected static function newFactory()
    {
        return SlaAppliedConversationFactory::new();
    }

    protected $fillable = [
        'conversation_id',
        'sla_id',
        'first_response_due_at',
        'first_response_at',
        'first_response_breached',
        'resolution_due_at',
        'resolved_at',
        'resolution_breached',
    ];

    protected $casts = [
        'first_response_due_at' => 'datetime',
        'first_response_at' => 'datetime',
        'first_response_breached' => 'boolean',
        'resolution_due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'resolution_breached' => 'boolean',
    ];

    /**
     * Get the conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the SLA policy.
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    /**
     * Scope to filter by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->whereHas('conversation', function ($q) use ($accountId) {
            $q->where('account_id', $accountId);
        });
    }

    /**
     * Scope to filter breached SLAs.
     */
    public function scopeBreached($query)
    {
        return $query->where(function ($q) {
            $q->where('first_response_breached', true)
                ->orWhere('resolution_breached', true);
        });
    }

    /**
     * Scope to filter approaching deadlines.
     */
    public function scopeApproaching($query)
    {
        $now = now();
        $firstResponseThreshold = $now->copy()->addMinutes(30);
        $resolutionThreshold = $now->copy()->addMinutes(60);

        return $query->where(function ($q) use ($now, $firstResponseThreshold, $resolutionThreshold) {
            $q->where(function ($subQ) use ($now, $firstResponseThreshold) {
                $subQ->whereNull('first_response_at')
                    ->whereNotNull('first_response_due_at')
                    ->whereBetween('first_response_due_at', [$now, $firstResponseThreshold]);
            })->orWhere(function ($subQ) use ($now, $resolutionThreshold) {
                $subQ->whereNull('resolved_at')
                    ->whereNotNull('resolution_due_at')
                    ->whereBetween('resolution_due_at', [$now, $resolutionThreshold]);
            });
        });
    }

    /**
     * Check if first response is approaching deadline (within 30 minutes).
     */
    public function isFirstResponseApproaching(): bool
    {
        if (! $this->first_response_due_at || $this->first_response_at) {
            return false;
        }

        return now()->diffInMinutes($this->first_response_due_at, false) <= 30
            && now()->diffInMinutes($this->first_response_due_at, false) > 0;
    }

    /**
     * Check if resolution is approaching deadline (within 1 hour).
     */
    public function isResolutionApproaching(): bool
    {
        if (! $this->resolution_due_at || $this->resolved_at) {
            return false;
        }

        return now()->diffInMinutes($this->resolution_due_at, false) <= 60
            && now()->diffInMinutes($this->resolution_due_at, false) > 0;
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClass(): string
    {
        if ($this->first_response_breached || $this->resolution_breached) {
            return 'bg-danger';
        }

        if ($this->isFirstResponseApproaching() || $this->isResolutionApproaching()) {
            return 'bg-warning';
        }

        return 'bg-success';
    }

    /**
     * Get status text.
     */
    public function getStatusText(): string
    {
        if ($this->first_response_breached) {
            return 'First Response Breached';
        }

        if ($this->resolution_breached) {
            return 'Resolution Breached';
        }

        if ($this->isFirstResponseApproaching()) {
            return 'First Response Due Soon';
        }

        if ($this->isResolutionApproaching()) {
            return 'Resolution Due Soon';
        }

        return 'On Track';
    }
}
