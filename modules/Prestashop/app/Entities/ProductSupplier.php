<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSupplier extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_product_supplier';

    protected $primaryKey = 'id_product_supplier';

    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'id_product_attribute',
        'id_supplier',
        'product_supplier_reference',
        'id_currency',
        'product_supplier_price_te',
    ];

    protected $casts = [
        'id_product' => 'integer',
        'id_product_attribute' => 'integer',
        'id_supplier' => 'integer',
        'id_currency' => 'integer',
        'product_supplier_price_te' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(Combination::class, 'id_product_attribute');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'id_currency');
    }
}
