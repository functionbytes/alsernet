<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_store';

    protected $primaryKey = 'id_store';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_country',
        'id_state',
        'name',
        'postcode',
        'city',
        'latitude',
        'longitude',
        'hours',
        'phone',
        'fax',
        'note',
        'email',
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
}
