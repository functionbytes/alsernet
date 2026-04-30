<?php

namespace Modules\Chat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Chat\Models\Accounts\Account;

class AuditLog extends Model
{
    protected $table = 'chat_audit_logs';

    protected $fillable = [
        'account_id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * Get the account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create audit log entry.
     */
    public static function log(string $event, ?Model $auditable = null, array $oldValues = [], array $newValues = []): void
    {
        $user = auth()->user();
        $request = request();

        static::create([
            'account_id' => $user?->account_id ?? $auditable?->account_id ?? null,
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'old_values' => ! empty($oldValues) ? $oldValues : null,
            'new_values' => ! empty($newValues) ? $newValues : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Scope for account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope for user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for event type.
     */
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get human-readable event description.
     */
    public function getDescriptionAttribute(): string
    {
        return match ($this->event) {
            'user.login' => 'User logged in',
            'user.logout' => 'User logged out',
            'conversation.created' => 'Conversation created',
            'conversation.assigned' => 'Conversation assigned',
            'conversation.status_changed' => 'Conversation status changed',
            'conversation.resolved' => 'Conversation resolved',
            'message.created' => 'ConversationMessage sent',
            'sla_policy.created' => 'SLA Policy created',
            'sla_policy.updated' => 'SLA Policy updated',
            'sla_policy.deleted' => 'SLA Policy deleted',
            'team.created' => 'Team created',
            'team.updated' => 'Team updated',
            'label.created' => 'Label created',
            default => ucwords(str_replace(['.', '_'], ' ', $this->event)),
        };
    }
}
