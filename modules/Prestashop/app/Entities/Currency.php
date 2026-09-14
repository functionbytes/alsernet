<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_currency';

    protected $primaryKey = 'id_currency';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'iso_code',
        'iso_code_num',
        'numeric_iso_code',
        'conversion_rate',
        'unofficial',
        'modified',
        'active',
        'sign',
        'symbol',
        'format',
        'blank',
        'decimals',
        'precision',
        'pattern',
    ];

    protected $casts = [
        'active' => 'boolean',
        'conversion_rate' => 'float',
    ];

    public function carts(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_currency', 'id_currency');
    }
}
