<?php

namespace Modules\Helpdesk\Models\Campaigns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Customer;

class BroadcastRecipient extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_broadcast_recipients';

    protected $fillable = [
        'broadcast_id',
        'customer_id',
        'external_id',
        'status',
        'error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public const STATUSES = ['pending', 'sent', 'delivered', 'read', 'failed'];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class, 'broadcast_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function markAsSent(string $externalId): void
    {
        $this->update([
            'status' => 'sent',
            'external_id' => $externalId,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
        ]);
    }
}
