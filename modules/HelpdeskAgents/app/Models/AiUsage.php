<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fila del ledger de uso LLM (helpdesk_ai_usage). Insert-only: no updated_at.
 */
class AiUsage extends Model
{
    public const UPDATED_AT = null;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_usage';

    protected $fillable = [
        'provider',
        'model',
        'feature',
        'tokens_in',
        'tokens_out',
        'duration_ms',
        'success',
        'status_code',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
            'duration_ms' => 'integer',
            'success' => 'boolean',
            'status_code' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
