<?php

namespace Modules\Prestashop\Entities\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Combination;
use Modules\Prestashop\Entities\Product;

class Stock extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_stock';

    protected $primaryKey = 'id_stock';

    public $timestamps = false;

    protected $fillable = [
        'id_warehouse',
        'id_product',
        'id_product_attribute',
        'reference',
        'isbn',
        'upc',
        'mpn',
        'physical_quantity',
        'usable_quantity',
        'price_te',
    ];

    protected $casts = [
        'id_warehouse' => 'integer',
        'id_product' => 'integer',
        'id_product_attribute' => 'integer',
        'price_te' => 'float',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(Combination::class, 'id_product_attribute');
    }
}
