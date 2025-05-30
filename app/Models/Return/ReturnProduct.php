<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnProduct extends Model
{
    protected $table = 'return_products';

    protected $primaryKey = 'id_return_product';

    protected $fillable = [
        'id_return_request',
        'product_code',
        'product_name',
        'product_quantity',
        'product_price',
        'id_catalog',
        'erp_product_id',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'id_return_request', 'id_return_request');
    }


}
