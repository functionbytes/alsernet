<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Modules\Ecommerce\Database\Factories\OrderFactory;
use Modules\Ecommerce\Enums\OrderAddressType;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\EcommercePayment\Models\Payment;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use HasFactory;
    use LogsActivity;

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
        'transaction_id',
        'payment_status',
        'customer_note',
        'admin_note',
        'completed_at',
        'delivery_date',
        'delivery_time',
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
            'delivery_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'payment_status',
                'total',
                'payment_method',
                'shipping_method',
                'coupon_code',
                'discount_amount',
                'customer_note',
                'admin_note',
                'completed_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ecommerce_order');
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

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->withTrashed();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', OrderAddressType::SHIPPING->value);
    }

    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', OrderAddressType::BILLING->value);
    }

    public function referral(): HasOne
    {
        return $this->hasOne(OrderReferral::class, 'order_id');
    }
}
