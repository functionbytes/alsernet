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
     * Maximum allowed length for a stored regex pattern.
     */
    private const MAX_PATTERN_LENGTH = 1000;

    /**
     * Maximum subject length evaluated against a regex (ReDoS mitigation).
     */
    private const MAX_REGEX_SUBJECT_LENGTH = 10000;

    /**
     * Check if the given text matches this rule.
     */
    public function matches(string $text): bool
    {
        return match ($this->match_type) {
            'exact' => mb_strtolower($text) === mb_strtolower($this->keyword),
            'regex' => $this->matchesRegex($text),
            default => str_contains(mb_strtolower($text), mb_strtolower($this->keyword)),
        };
    }

    /**
     * Safely evaluate a stored regex against incoming text.
     *
     * Guards against ReDoS and malformed patterns: bounds the pattern and
     * subject length, and treats compilation/runtime errors as a non-match.
     */
    private function matchesRegex(string $text): bool
    {
        $pattern = (string) $this->keyword;

        if ($pattern === '' || mb_strlen($pattern) > self::MAX_PATTERN_LENGTH) {
            return false;
        }

        if (mb_strlen($text) > self::MAX_REGEX_SUBJECT_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_REGEX_SUBJECT_LENGTH);
        }

        try {
            $result = @preg_match($pattern, $text);
        } catch (\Throwable) {
            return false;
        }

        if ($result === false || preg_last_error() !== PREG_NO_ERROR) {
            return false;
        }

        return $result === 1;
    }
}
