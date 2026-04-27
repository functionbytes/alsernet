<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Database\Factories\TaxFactory;

class Tax extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_taxes';

    protected static function newFactory(): TaxFactory
    {
        return TaxFactory::new();
    }

    protected $fillable = [
        'title',
        'percentage',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'float',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TaxRule::class, 'tax_id')->orderBy('order');
    }
}
