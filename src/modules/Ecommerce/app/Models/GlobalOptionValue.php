<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalOptionValue extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_global_option_values';

    protected $fillable = [
        'option_id',
        'option_value',
        'affect_price',
        'affect_type',
        'order',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(GlobalOption::class, 'option_id');
    }
}
