<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\Database\Factories\ProductRestockAlertFactory;

class ProductRestockAlert extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_restock_alerts';

    protected static function newFactory(): ProductRestockAlertFactory
    {
        return ProductRestockAlertFactory::new();
    }

    protected $fillable = [
        'product_id',
        'email',
        'customer_id',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopePendingNotification(Builder $query): Builder
    {
        return $query->whereNull('notified_at');
    }
}
