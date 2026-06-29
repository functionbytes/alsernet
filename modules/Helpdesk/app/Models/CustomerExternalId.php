<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vincula un Customer del Helpdesk con su ID en una plataforma externa
 * (PrestaShop, ERP Alvarez, Shopify, WooCommerce).
 *
 * Un mismo customer puede tener varias entradas — una por plataforma.
 * El par (platform, external_id) es único globalmente.
 */
class CustomerExternalId extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_customer_external_ids';

    protected $fillable = [
        'customer_id',
        'platform',
        'external_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeForExternal(Builder $query, string $platform, string $externalId): Builder
    {
        return $query->where('platform', $platform)->where('external_id', $externalId);
    }
}
