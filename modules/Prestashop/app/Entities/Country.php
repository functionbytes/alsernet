<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Country extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_country';

    protected $primaryKey = 'id_country';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_zone',
        'id_currency',
        'iso_code',
        'call_prefix',
        'name',
        'contains_states',
        'need_identification_number',
        'need_zip_code',
        'zip_code_format',
    ];

    protected $casts = [
        'id_zone' => 'integer',
        'id_currency' => 'integer',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'id_zone');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'id_currency');
    }
}
