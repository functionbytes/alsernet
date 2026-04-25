<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Database\Factories\ShippingFactory;

class Shipping extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_shipping';

    protected static function newFactory(): ShippingFactory
    {
        return ShippingFactory::new();
    }

    protected $fillable = [
        'title',
        'country',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(ShippingRule::class, 'shipping_id');
    }
}
