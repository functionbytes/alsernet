<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Tax\Tax;
use Modules\Prestashop\Entities\Tax\TaxRulesGroup;

class TaxRule extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_tax_rule';

    protected $primaryKey = 'id_tax_rule';

    public $timestamps = false;

    protected $fillable = [
        'id_tax_rule',
        'id_tax_rules_group',
        'id_country',
        'id_state',
        'zipcode_from',
        'zipcode_to',
        'id_tax',
        'behavior',
        'description',
    ];

    protected $casts = [
        'id_tax_rule' => 'integer',
        'id_tax_rules_group' => 'integer',
        'id_country' => 'integer',
        'id_state' => 'integer',
        'id_tax' => 'integer',
    ];

    public function taxRulesGroup(): BelongsTo
    {
        return $this->belongsTo(TaxRulesGroup::class, 'id_tax_rules_group');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'id_country');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'id_state');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'id_tax');
    }
}
