<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CustomerInbox extends Pivot
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_customer_inboxes';

    protected $fillable = [
        'customer_id',
        'inbox_id',
        'source_id',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }
}
