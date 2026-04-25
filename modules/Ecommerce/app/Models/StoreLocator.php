<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreLocator extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_store_locators';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'country',
        'state',
        'city',
        'is_primary',
        'is_shipping_location',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_shipping_location' => 'boolean',
        ];
    }
}
