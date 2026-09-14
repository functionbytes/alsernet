<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_carrier';

    protected $primaryKey = 'id_carrier';

    public $timestamps = false;

    protected $fillable = [
        'id_reference',
        'name',
        'url',
        'delay',
        'range_behavior',
        'is_module',
        'position',
        'max_width',
        'max_height',
        'max_depth',
        'max_weight',
        'grade',
    ];

    protected $casts = [
        'is_module' => 'boolean',
        'id_reference' => 'integer',
        'position' => 'integer',
        'max_width' => 'float',
        'max_height' => 'float',
        'max_depth' => 'float',
        'max_weight' => 'float',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Shop', 'id_shop', 'id_shop');
    }

    public function carts(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_carrier', 'id_carrier');
    }
}
