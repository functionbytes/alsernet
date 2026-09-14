<?php

namespace Modules\HelpdeskPrestashop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;

class AssistedCart extends Model
{
    public const STATUS_BUILDING = 'building';

    public const STATUS_SENT = 'sent';

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_ABANDONED = 'abandoned';

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_assisted_carts';

    protected $fillable = [
        'customer_id',
        'conversation_id',
        'created_by_user_id',
        'status',
        'discount_code',
        'discount_amount',
        'shipping_amount',
        'currency',
        'ecommerce_order_id',
        'order_code',
        'payment_url',
        'notes',
        'sent_at',
        'converted_at',
        'abandoned_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'sent_at' => 'datetime',
            'converted_at' => 'datetime',
            'abandoned_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssistedCartItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->items->sum(fn (AssistedCartItem $item) => $item->line_total), 2),
        );
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->subtotal - (float) $this->discount_amount + (float) $this->shipping_amount, 2),
        );
    }

    protected function unitsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->items->sum('quantity'),
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                self::STATUS_BUILDING => 'Activo',
                self::STATUS_SENT => 'Pendiente de pago',
                self::STATUS_ORDERED => 'Convertido',
                self::STATUS_ABANDONED => 'Abandonado',
                default => ucfirst((string) $this->status),
            },
        );
    }

    protected function statusGroup(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                self::STATUS_BUILDING, self::STATUS_SENT => 'active',
                self::STATUS_ORDERED => 'converted',
                self::STATUS_ABANDONED => 'abandoned',
                default => 'active',
            },
        );
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_BUILDING, self::STATUS_SENT], true);
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeBuilding(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BUILDING);
    }
}
