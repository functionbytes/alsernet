<?php

namespace Modules\HelpdeskAiPrompts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HelpdeskAiPrompts\Models\Concerns\HasPromptVersions;

class AiPromptCase extends Model
{
    use HasFactory, HasPromptVersions;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_prompt_cases';

    protected $fillable = [
        'key',
        'name',
        'description',
        'priority',
        'is_active',
        'instructions',
        'allowed_tools',
        'knowledge_keys',
        'escalation',
        'escalation_message',
        'examples',
        'keywords',
        'filters',
        'test_questions',
        'channel',
        'procedure_flow_id',
        'procedure_input',
        'procedure_outputs',
        'version',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
            'allowed_tools' => 'array',
            'knowledge_keys' => 'array',
            'examples' => 'array',
            'keywords' => 'array',
            'filters' => 'array',
            'test_questions' => 'array',
            'procedure_flow_id' => 'integer',
            'procedure_input' => 'array',
            'procedure_outputs' => 'array',
            'version' => 'integer',
        ];
    }

    public function versionSubjectType(): string
    {
        return 'case';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Casos globales (channel null) o del canal indicado.
     */
    public function scopeForChannel(Builder $query, ?string $channel): Builder
    {
        return $query->where(function (Builder $q) use ($channel) {
            $q->whereNull('channel');

            if ($channel !== null) {
                $q->orWhere('channel', $channel);
            }
        });
    }
}
