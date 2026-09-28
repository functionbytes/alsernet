<?php

namespace Modules\HelpdeskAiPrompts\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptVersion extends Model
{
    public $timestamps = false;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_prompt_versions';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'version',
        'snapshot',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'version' => 'integer',
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
