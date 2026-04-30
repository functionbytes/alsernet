<?php

namespace Modules\Chat\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class PrestashopCustomerSync extends Model
{
    protected $table = 'chat_customers_sync';

    protected $fillable = [
        'account_id',
        'prestashop_customer_id',
        'customer_id',
        'email',
        'firstname',
        'lastname',
        'additional_attributes',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'additional_attributes' => 'array',
            'last_sync_at' => 'datetime',
        ];
    }

    /**
     * Get the account that owns the sync record.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the chat customer.
     */
    public function chatCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Update last sync timestamp.
     */
    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    /**
     * Get full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->firstname.' '.$this->lastname);
    }
}
