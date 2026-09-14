<?php

namespace Modules\Supplier\Models\Ai;

use Illuminate\Database\Eloquent\Model;

class AiBatchMetric extends Model
{
    protected $table = 'supplier_ai_batch_metrics';

    public const UPDATED_AT = null;

    protected $fillable = [
        'batch_id',
        'total_items',
        'successful',
        'failed',
        'rate_limited',
        'budget_exceeded',
        'total_cost',
        'total_tokens',
        'avg_latency_ms',
        'duration_seconds',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_items' => 'integer',
            'successful' => 'integer',
            'failed' => 'integer',
            'rate_limited' => 'integer',
            'budget_exceeded' => 'integer',
            'total_cost' => 'decimal:4',
            'total_tokens' => 'integer',
            'avg_latency_ms' => 'decimal:2',
            'duration_seconds' => 'decimal:3',
            'errors' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
