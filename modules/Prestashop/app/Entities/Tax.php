<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Tax\Tax;

class Tax extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_tax';

    protected $primaryKey = 'id_tax';

    public $timestamps = false;

    protected $fillable = [
        'id_tax',
        'name',
        'rate',
        'active',
        'deleted',
    ];

    protected $casts = [
        'active' => 'boolean',
        'deleted' => 'boolean',
        'id_tax' => 'integer',
        'rate' => 'float',
    ];

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'id_tax');
    }
}
