<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ABANDONED = 'abandoned';

    protected $connection = 'helpdesk';

    protected $table = 'engagement_automation_runs';

    protected $fillable = [
        'flow_id',
        'session_token',
        'customer_id',
        'current_node_id',
        'status',
        'context',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(AutomationFlow::class, 'flow_id');
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RUNNING);
    }
}
