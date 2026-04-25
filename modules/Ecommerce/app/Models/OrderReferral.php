<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReferral extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_order_referrals';

    protected $fillable = [
        'order_id',
        'ip',
        'landing_domain',
        'landing_page',
        'landing_params',
        'referral_domain',
        'referral_url',
        'user_agent',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
