<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    protected $table = 'ecommerce_search_logs';

    public $timestamps = false;

    protected $fillable = [
        'query',
        'results_count',
        'customer_id',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
