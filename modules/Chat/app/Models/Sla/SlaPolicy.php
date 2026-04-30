<?php

namespace Modules\Chat\Models\Sla;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Chat\Database\Factories\SlaPolicyFactory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;

class SlaPolicy extends Model
{
    use HasFactory;

    protected $table = 'chat_sla_policies';

    protected static function newFactory()
    {
        return SlaPolicyFactory::new();
    }

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'first_response_time_minutes',
        'resolution_time_minutes',
        'is_active',
        'business_hours_only',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'business_hours_only' => 'boolean',
            'first_response_time_minutes' => 'integer',
            'resolution_time_minutes' => 'integer',
        ];
    }

    /**
     * Get the account that owns the SLA policy.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all applied conversations for this SLA policy.
     */
    public function appliedConversations(): HasMany
    {
        return $this->hasMany(SlaAppliedConversation::class, 'sla_id');
    }

    /**
     * Get all conversations linked to this SLA policy through applied conversations.
     */
    public function conversations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Conversation::class,
            SlaAppliedConversation::class,
            'sla_id',
            'id',
            'id',
            'conversation_id'
        );
    }

    /**
     * Scope to filter active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Get formatted first response time.
     */
    public function getFormattedFirstResponseTimeAttribute(): string
    {
        if (! $this->first_response_time_minutes) {
            return 'No target set';
        }

        return $this->formatMinutes($this->first_response_time_minutes);
    }

    /**
     * Get formatted resolution time.
     */
    public function getFormattedResolutionTimeAttribute(): string
    {
        if (! $this->resolution_time_minutes) {
            return 'No target set';
        }

        return $this->formatMinutes($this->resolution_time_minutes);
    }

    /**
     * Format minutes to human-readable time.
     */
    protected function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours === 1 ? '1 hour' : "{$hours} hours";
        }

        return "{$hours}h {$remainingMinutes}m";
    }
}
