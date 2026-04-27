<?php

namespace Modules\Ecommerce\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ecommerce_subscriptions';

    protected $fillable = [
        'customer_id',
        'product_id',
        'qty',
        'interval',
        'price',
        'status',
        'next_billing_at',
        'last_billed_at',
        'cancelled_at',
    ];

    public function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'next_billing_at' => 'datetime',
            'last_billed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function calculateNextBilling(): Carbon
    {
        return match ($this->interval) {
            'weekly' => now()->addWeek(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }
}
