<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiAgent extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agents';

    protected $fillable = [
        'name',
        'description',
        'system_prompt',
        'knowledge_sources',
        'enabled_channels',
        'escalation_keywords',
        'max_messages_before_escalation',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'knowledge_sources' => 'array',
            'enabled_channels' => 'array',
            'escalation_keywords' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function handlesChannel(string $channel): bool
    {
        $channels = $this->enabled_channels ?? [];

        return empty($channels) || in_array($channel, $channels, true);
    }

    public function hasEscalationKeyword(string $text): bool
    {
        $keywords = $this->escalation_keywords ?? [];

        if (empty($keywords)) {
            return false;
        }

        $lower = mb_strtolower($text);

        foreach ($keywords as $keyword) {
            if (str_contains($lower, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
