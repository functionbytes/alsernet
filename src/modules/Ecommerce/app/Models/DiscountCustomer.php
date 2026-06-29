<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountCustomer extends Model
{
    protected $table = 'ecommerce_discount_customers';

    protected $fillable = ['discount_id', 'customer_id'];

    public $timestamps = false;

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
