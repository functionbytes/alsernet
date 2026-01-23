<?php

namespace Modules\HelpdeskChat\Models\Conversations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Customers\Customer;

class ConversationSession extends Model
{
    protected $fillable = [
        'account_id',
        'token',
        'prestashop_customer_id',
        'helpdesk_customer_id',
        'session_id',
        'session_data',
        'last_activity_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'session_data' => 'array',
            'last_activity_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the account that owns the chat session.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the helpdesk customer for this session.
     */
    public function helpdeskCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'helpdesk_customer_id');
    }

    /**
     * Get all conversation for this session.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Update last activity timestamp.
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Check if session is active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Deactivate session.
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Activate session.
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Check if session is linked to PrestaShop customer.
     */
    public function isLinkedToPrestaShop(): bool
    {
        return $this->prestashop_customer_id !== null;
    }
}
