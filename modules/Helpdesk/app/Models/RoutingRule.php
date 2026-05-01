<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

class RoutingRule extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_routing_rules';

    public const MATCH_TYPES = [
        'contains' => 'Contiene',
        'exact' => 'Coincidencia exacta',
        'regex' => 'Expresión regular',
    ];

    protected $fillable = [
        'keyword',
        'match_type',
        'assign_to_user_id',
        'assign_to_team_id',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'assign_to_user_id' => 'integer',
            'assign_to_team_id' => 'integer',
        ];
    }

    /**
     * Check if the given text matches this rule.
     */
    public function matches(string $text): bool
    {
        return match ($this->match_type) {
            'exact' => mb_strtolower($text) === mb_strtolower($this->keyword),
            'regex' => (bool) @preg_match($this->keyword, $text),
            default => str_contains(mb_strtolower($text), mb_strtolower($this->keyword)),
        };
    }
}
