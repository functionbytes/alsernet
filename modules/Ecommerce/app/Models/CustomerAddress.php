<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\Database\Factories\CustomerAddressFactory;

class CustomerAddress extends Model
{
    use HasFactory;

    protected static function newFactory(): CustomerAddressFactory
    {
        return CustomerAddressFactory::new();
    }

    protected $table = 'ecommerce_customer_addresses';

    protected $fillable = [
        'customer_id',
        'name',
        'phone',
        'email',
        'country',
        'state',
        'city',
        'address',
        'zip_code',
        'is_default',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
