<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class State extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_state';

    protected $primaryKey = 'id_state';

    public $timestamps = false;

    protected $fillable = [
        'id_country',
        'id_zone',
        'iso_code',
        'name',
    ];

    protected $casts = [
        'id_country' => 'integer',
        'id_zone' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'id_country');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'id_zone');
    }
}
