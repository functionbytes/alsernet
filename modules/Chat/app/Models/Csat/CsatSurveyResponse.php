<?php

namespace Modules\Chat\Models\Csat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;

class CsatSurveyResponse extends Model
{
    protected $table = 'chat_csat_survey_responses';

    protected $fillable = [
        'account_id',
        'conversation_id',
        'customer_id',
        'assigned_agent_id',
        'rating',
        'feedback',
        'survey_token',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'rating' => 'integer',
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
     * Get the conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the assigned agent.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /**
     * Check if survey is completed.
     */
    public function isCompleted(): bool
    {
        return ! is_null($this->submitted_at);
    }

    /**
     * Get rating emoji.
     */
    public function getRatingEmojiAttribute(): string
    {
        return match ($this->rating) {
            1 => '😞',
            2 => '😕',
            3 => '😐',
            4 => '🙂',
            5 => '😀',
            default => '❓',
        };
    }

    /**
     * Get rating label.
     */
    public function getRatingLabelAttribute(): string
    {
        return match ($this->rating) {
            1 => 'Very Dissatisfied',
            2 => 'Dissatisfied',
            3 => 'Neutral',
            4 => 'Satisfied',
            5 => 'Very Satisfied',
            default => 'Unknown',
        };
    }

    /**
     * Scope for completed surveys.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Scope for pending surveys.
     */
    public function scopePending($query)
    {
        return $query->whereNull('submitted_at');
    }

    /**
     * Scope for account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }
}
