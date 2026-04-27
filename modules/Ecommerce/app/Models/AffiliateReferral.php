<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateReferral extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_affiliate_referrals';

    protected $fillable = [
        'affiliate_id',
        'order_id',
        'customer_id',
        'order_total',
        'commission_amount',
        'status',
    ];

    public function casts(): array
    {
        return [
            'order_total' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
