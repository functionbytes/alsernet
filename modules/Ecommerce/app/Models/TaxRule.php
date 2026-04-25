<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\Database\Factories\TaxRuleFactory;

class TaxRule extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_tax_rules';

    protected static function newFactory(): TaxRuleFactory
    {
        return TaxRuleFactory::new();
    }

    protected $fillable = [
        'tax_id',
        'country',
        'state',
        'city',
        'zip_code',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'float',
        ];
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
