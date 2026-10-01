<?php

namespace Modules\HelpdeskAiPrompts\Models;

use Illuminate\Database\Eloquent\Model;

class AiRegressionReport extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_regression_reports';

    protected $fillable = [
        'case_key',
        'case_version',
        'is_draft',
        'status',
        'triggered_by',
        'questions_total',
        'regressions',
        'avg_score',
        'cost_eur',
        'summary',
        'results',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'case_version' => 'integer',
            'is_draft' => 'boolean',
            'triggered_by' => 'integer',
            'questions_total' => 'integer',
            'regressions' => 'integer',
            'avg_score' => 'float',
            'cost_eur' => 'float',
            'summary' => 'array',
            'results' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}
