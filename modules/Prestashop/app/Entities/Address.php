<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_address';

    protected $primaryKey = 'id_address';

    public $timestamps = false;

    protected $fillable = [
        'id_country',
        'id_state',
        'country',
        'alias',
        'company',
        'lastname',
        'firstname',
        'postcode',
        'city',
        'other',
        'phone',
        'phone_mobile',
        'vat_number',
        'dni',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_country' => 'integer',
        'id_state' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'id_country');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'id_state');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Customer', 'id_customer', 'id_customer');
    }

    public function cartsAsDelivery(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_address_delivery', 'id_address');
    }

    public function cartsAsInvoice(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_address_invoice', 'id_address');
    }
}
