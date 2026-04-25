<?php

namespace Modules\EcommercePayment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecommerce\Models\Order;
use Modules\EcommercePayment\Database\Factories\PaymentFactory;
use Modules\EcommercePayment\Enums\PaymentStatus;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }

    protected $table = 'ecommerce_payments';

    protected $fillable = [
        'charge_id',
        'order_id',
        'amount',
        'payment_fee',
        'currency',
        'payment_channel',
        'description',
        'status',
        'payment_type',
        'customer_id',
        'customer_type',
        'refunded_amount',
        'refund_note',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'payment_fee' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }
}
