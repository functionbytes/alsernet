<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Tax\TaxRulesGroup;

class TaxRulesGroup extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_tax_rules_group';

    protected $primaryKey = 'id_tax_rules_group';

    public $timestamps = false;

    protected $fillable = [
        'id_tax_rules_group',
        'name',
        'active',
        'deleted',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'active' => 'boolean',
        'deleted' => 'boolean',
        'id_tax_rules_group' => 'integer',
    ];

    public function taxRulesGroup(): BelongsTo
    {
        return $this->belongsTo(TaxRulesGroup::class, 'id_tax_rules_group');
    }
}
