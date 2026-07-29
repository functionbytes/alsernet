<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Combination extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_product_attribute';

    protected $primaryKey = 'id_product_attribute';

    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'reference',
        'supplier_reference',
        'isbn',
        'upc',
        'mpn',
        'wholesale_price',
        'price',
        'unit_price_impact',
        'ecotax',
        'quantity',
        'weight',
        'default_on',
    ];

    protected $casts = [
        'id_product' => 'integer',
        'quantity' => 'integer',
        'wholesale_price' => 'float',
        'price' => 'float',
        'unit_price_impact' => 'float',
        'ecotax' => 'float',
        'weight' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }
}
