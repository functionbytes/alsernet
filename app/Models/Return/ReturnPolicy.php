<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;

class ReturnPolicy extends Model
{
    protected $table = 'return_policies';
    protected $primaryKey = 'id_return_policy';

    protected $fillable = [
        'name', 'return_days', 'conditions', 'active', 'id_shop', 'applies_to_categories',
        'applies_to_products', 'min_order_amount', 'max_return_amount', 'requires_original_packaging',
        'allows_opened_products', 'shipping_cost_coverage', 'restocking_fee_percentage'
    ];

}
