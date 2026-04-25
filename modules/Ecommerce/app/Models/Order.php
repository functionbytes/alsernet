<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Modules\Ecommerce\Database\Factories\OrderFactory;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\EcommercePayment\Models\Payment;

class Order extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_orders';

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    protected $fillable = [
        'code',
        'token',
        'customer_id',
        'status',
        'sub_total',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total',
        'coupon_code',
        'discount_description',
        'shipping_method',
        'shipping_option',
        'payment_method',
        'payment_status',
        'customer_note',
        'admin_note',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'sub_total' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->code)) {
                $order->code = 'ORD-'.strtoupper(uniqid());
            }

            if (empty($order->token)) {
                $order->token = 'OR'.time().'_'.strtoupper(Str::random(8));
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderHistory::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->withTrashed();
    }
}
