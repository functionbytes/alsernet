<?php

namespace Modules\Engagement\Services;

use Modules\Engagement\Models\ConversionGoal;
use Modules\Engagement\Models\Event;

class ConversionMatcher
{
    /**
     * Match a single event against active goals and return goal IDs that fired.
     */
    public function matchEvent(Event $event): array
    {
        $matched = [];

        $goals = ConversionGoal::query()
            ->forInbox($event->inbox_id)
            ->active()
            ->get();

        foreach ($goals as $goal) {
            if ($this->matches($goal, $event)) {
                $matched[] = $goal->id;
            }
        }

        return $matched;
    }

    /**
     * Compute funnel completion stats for a goal.
     * Returns: [{step: name, visitors: N, drop_pct: X}]
     */
    public function funnelStats(ConversionGoal $goal, int $sinceDays = 30): array
    {
        $steps = $goal->funnel_steps ?? [];
        if (empty($steps)) {
            return [];
        }

        $from = now()->subDays($sinceDays);

        // Find sessions that completed each step in order
        $previousSessions = null;
        $stats = [];
        $startCount = 0;

        foreach ($steps as $i => $step) {
            $query = Event::query()
                ->forInbox($goal->inbox_id)
                ->where('event_name', $step)
                ->where('occurred_at', '>=', $from);

            if ($previousSessions !== null) {
                $query->whereIn('session_token', $previousSessions);
            }

            $sessions = $query->distinct('session_token')->pluck('session_token')->all();
            $count = count($sessions);

            if ($i === 0) {
                $startCount = $count;
            }

            $stats[] = [
                'step' => $step,
                'visitors' => $count,
                'drop_pct' => $startCount > 0 ? round((1 - $count / $startCount) * 100, 1) : 0,
            ];

            $previousSessions = $sessions;
        }

        return $stats;
    }

    private function matches(ConversionGoal $goal, Event $event): bool
    {
        return match ($goal->type) {
            ConversionGoal::TYPE_EVENT => $event->event_name === $goal->event_name,
            ConversionGoal::TYPE_URL_VISIT => $event->event_name === 'page_view'
                && $goal->url_pattern
                && str_contains((string) ($event->properties['url'] ?? ''), $goal->url_pattern),
            ConversionGoal::TYPE_SEGMENT_CHANGE => $event->event_name === 'segment_changed'
                && ($event->properties['to'] ?? null) === $goal->target_segment,
            default => false,
        };
    }
}
