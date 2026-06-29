<?php

namespace Modules\Remarketing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'remarketing_carts';

    protected $fillable = [
        'store_id',
        'customer_id',
        'external_id',
        'visitor_id',
        'email',
        'items',
        'total',
        'currency',
        'status',
        'abandoned_at',
        'recovered_at',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total' => 'decimal:2',
            'abandoned_at' => 'datetime',
            'recovered_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeAbandoned(Builder $query): Builder
    {
        return $query->where('status', 'abandoned');
    }

    public function scopeRecovered(Builder $query): Builder
    {
        return $query->where('status', 'recovered');
    }
}
