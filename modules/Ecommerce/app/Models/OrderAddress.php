<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Locations\Models\City;
use Modules\Locations\Models\Country;
use Modules\Locations\Models\State;

class OrderAddress extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_order_addresses';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'country',
        'state',
        'city',
        'country_id',
        'state_id',
        'city_id',
        'address',
        'order_id',
        'type',
        'zip_code',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function locationCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function locationState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function locationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
