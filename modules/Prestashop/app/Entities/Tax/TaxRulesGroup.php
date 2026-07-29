<?php

namespace Modules\Prestashop\Entities\Tax;

use Illuminate\Database\Eloquent\Model;

class TaxRulesGroup extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_tax_rules_group';

    protected $primaryKey = 'id_tax_rules_group';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'active',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'active' => 'boolean',
    ];
}
