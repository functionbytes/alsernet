<?php

namespace Modules\HelpdeskChat\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskChat\Models\Customer;

class PrestashopCustomerSync extends Model
{
    protected $table = 'prestashop_customers_sync';

    protected $fillable = [
        'account_id',
        'prestashop_customer_id',
        'helpdesk_customer_id',
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
     * Get the helpdesk customer.
     */
    public function helpdeskCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'helpdesk_customer_id');
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
