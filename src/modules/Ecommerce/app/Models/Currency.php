<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_currencies';

    protected $fillable = [
        'title',
        'symbol',
        'is_prefix_symbol',
        'decimals',
        'order',
        'is_default',
        'exchange_rate',
    ];

    protected function casts(): array
    {
        return [
            'is_prefix_symbol' => 'boolean',
            'is_default' => 'boolean',
            'exchange_rate' => 'float',
        ];
    }
}
