<?php

namespace Modules\HelpdeskSla\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Events\SlaBreached;
use Modules\Helpdesk\Models\BusinessHour;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\SlaPolicy;
use Modules\HelpdeskSla\Events\SlaWarningThreshold;
use Modules\HelpdeskSla\Models\ConversationSlaBreach;

/**
 * Central SLA engine for Helpdesk conversations.
 *
 * Reuses the canonical Modules\Helpdesk\Models\SlaPolicy (hours-based, routed by
 * priority/category) and the real helpdesk_business_hours calendar. Replaces the
 * legacy hardcoded 15-minute command and the broken core listener.
 */
class ConversationSlaService
{
    private const BH_CACHE_KEY = 'helpdesksla:business_hours_schedule';

    private const PRIORITY_CACHE_KEY = 'helpdesksla:priority_slug_map';

    /**
     * Conversation.priority is stored in English (low/normal/high/urgent) while
     * helpdesk_priorities.slug is in Spanish (baja/normal/alta/urgente/critico).
     * This aliases the English value to the canonical slug used by the policies.
     */
    private const PRIORITY_SLUG_ALIASES = [
        'low' => 'baja',
        'normal' => 'normal',
        'high' => 'alta',
        'urgent' => 'urgente',
        'critical' => 'critico',
    ];

    /**
     * Resolve the SLA policy applicable to a conversation.
     *
     * Conversations have no category, so we match an active policy by the
     * conversation priority (priority_id with category_id NULL) and fall back to
     * a global policy (priority_id NULL, category_id NULL).
     */
    public function getApplicablePolicy(Conversation $conversation): ?SlaPolicy
    {
        $priorityId = $this->priorityIdForSlug($conversation->priority);

        if ($priorityId !== null) {
            $policy = SlaPolicy::query()
                ->active()
                ->where('priority_id', $priorityId)
                ->whereNull('category_id')
                ->first();

            if ($policy) {
                return $policy;
            }
        }

        return SlaPolicy::query()
            ->active()
            ->whereNull('priority_id')
            ->whereNull('category_id')
            ->first();
    }

    /**
     * Initialize SLA tracking for a conversation. Idempotent: skips when a policy
     * has already been applied.
     */
    public function initialize(Conversation $conversation): void
    {
        if ($conversation->sla_policy_id !== null) {
            return;
        }

        $policy = $this->getApplicablePolicy($conversation);

        if (! $policy) {
            return;
        }

        $start = $conversation->created_at ?? now();
        $businessOnly = (bool) $policy->business_hours_only;

        $updates = ['sla_policy_id' => $policy->id];

        if ($policy->first_response_time_hours) {
            $updates['sla_first_response_due_at'] = $this->addBusinessHours($start, (int) $policy->first_response_time_hours, $businessOnly);
        }

        if ($policy->resolution_time_hours) {
            $updates['sla_resolution_due_at'] = $this->addBusinessHours($start, (int) $policy->resolution_time_hours, $businessOnly);
        }

        $conversation->updateQuietly($updates);
    }

    /**
     * Mark the first agent response, stopping the first-response clock. Targeted
     * update so it never fires model events and is idempotent.
     */
    public function markFirstResponse(int $conversationId): void
    {
        Conversation::query()
            ->whereKey($conversationId)
            ->whereNull('first_response_at')
            ->update(['first_response_at' => now()]);
    }

    /**
     * Detect first-response and resolution breaches across open conversations,
     * persist a breach record, flag the conversation and broadcast SlaBreached.
     *
     * @return int number of breaches detected
     */
    public function checkBreaches(): int
    {
        $count = 0;

        Conversation::query()
            ->open()
            ->whereNull('first_response_at')
            ->where('sla_first_response_breached', false)
            ->whereNotNull('sla_first_response_due_at')
            ->where('sla_first_response_due_at', '<', now())
            ->whereNull('sla_paused_at')
            ->cursor()
            ->each(function (Conversation $conversation) use (&$count): void {
                $this->recordBreach($conversation, ConversationSlaBreach::TYPE_FIRST_RESPONSE, $conversation->sla_first_response_due_at);

                $conversation->updateQuietly([
                    'sla_first_response_breached' => true,
                    'sla_warned_at' => $conversation->sla_warned_at ?? now(),
                ]);

                SlaBreached::dispatch($conversation);
                $count++;
            });

        Conversation::query()
            ->open()
            ->where('sla_resolution_breached', false)
            ->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '<', now())
            ->whereNull('sla_paused_at')
            ->cursor()
            ->each(function (Conversation $conversation) use (&$count): void {
                $this->recordBreach($conversation, ConversationSlaBreach::TYPE_RESOLUTION, $conversation->sla_resolution_due_at);

                $conversation->updateQuietly(['sla_resolution_breached' => true]);

                SlaBreached::dispatch($conversation);
                $count++;
            });

        return $count;
    }

    /**
     * Warn about open conversations approaching their SLA deadline (past the
     * policy's warning_threshold_percent but not yet breached). Warns once per
     * conversation, deduped via sla_warned_at.
     *
     * @return int number of warnings sent
     */
    public function sendWarnings(): int
    {
        $count = 0;
        $policies = [];

        Conversation::query()
            ->open()
            ->whereNull('sla_warned_at')
            ->whereNotNull('sla_policy_id')
            ->whereNull('sla_paused_at')
            ->where(function ($query): void {
                $query->where(function ($q): void {
                    $q->whereNull('first_response_at')
                        ->where('sla_first_response_breached', false)
                        ->whereNotNull('sla_first_response_due_at')
                        ->where('sla_first_response_due_at', '>', now());
                })->orWhere(function ($q): void {
                    $q->where('sla_resolution_breached', false)
                        ->whereNotNull('sla_resolution_due_at')
                        ->where('sla_resolution_due_at', '>', now());
                });
            })
            ->cursor()
            ->each(function (Conversation $conversation) use (&$count, &$policies): void {
                $policyId = $conversation->sla_policy_id;
                $policy = $policies[$policyId] ??= SlaPolicy::find($policyId);
                $threshold = (int) ($policy->warning_threshold_percent ?? 80);

                $approaching = $this->approachingWarning($conversation, $threshold);

                if ($approaching === null) {
                    return;
                }

                [$type, $percent] = $approaching;

                $conversation->updateQuietly(['sla_warned_at' => now()]);
                SlaWarningThreshold::dispatch($conversation, $type, $percent);
                $count++;
            });

        return $count;
    }

    /**
     * Recompute SLA due dates after a priority change. Re-resolves the policy,
     * recomputes due dates from the creation time and re-evaluates breach flags.
     * No-op on closed conversations.
     */
    public function recalculate(Conversation $conversation): void
    {
        if ($conversation->closed_at !== null) {
            return;
        }

        $policy = $this->getApplicablePolicy($conversation);

        if (! $policy) {
            return;
        }

        $start = $conversation->created_at ?? now();
        $businessOnly = (bool) $policy->business_hours_only;
        $updates = ['sla_policy_id' => $policy->id];

        if ($policy->first_response_time_hours && $conversation->first_response_at === null) {
            $updates['sla_first_response_due_at'] = $this->addBusinessHours($start, (int) $policy->first_response_time_hours, $businessOnly);
            $updates['sla_first_response_breached'] = false;
        }

        if ($policy->resolution_time_hours) {
            $updates['sla_resolution_due_at'] = $this->addBusinessHours($start, (int) $policy->resolution_time_hours, $businessOnly);
            $updates['sla_resolution_breached'] = false;
        }

        $conversation->updateQuietly($updates);
    }

    /**
     * Finalize SLA tracking when a conversation is closed: record a resolution
     * breach if it was closed after its due date, and resolve pending breaches.
     */
    public function finalize(Conversation $conversation): void
    {
        $closedAt = $conversation->closed_at;

        if (
            $closedAt
            && $conversation->sla_resolution_due_at
            && ! $conversation->sla_resolution_breached
            && $closedAt->greaterThan($conversation->sla_resolution_due_at)
        ) {
            $this->recordBreach($conversation, ConversationSlaBreach::TYPE_RESOLUTION, $conversation->sla_resolution_due_at);
            $conversation->updateQuietly(['sla_resolution_breached' => true]);
        }

        ConversationSlaBreach::query()
            ->where('conversation_id', $conversation->id)
            ->unresolved()
            ->update(['resolved' => true, 'resolved_at' => now()]);
    }

    /**
     * Pause the SLA clock (e.g. while snoozed / waiting for the customer).
     */
    public function pauseSla(Conversation $conversation): void
    {
        if ($conversation->sla_paused_at !== null) {
            return;
        }

        $conversation->updateQuietly(['sla_paused_at' => now()]);
    }

    /**
     * Resume the SLA clock, extending due dates by the paused duration.
     */
    public function resumeSla(Conversation $conversation): void
    {
        if ($conversation->sla_paused_at === null) {
            return;
        }

        $pausedMinutes = (int) round(abs($conversation->sla_paused_at->diffInMinutes(now())));

        $updates = [
            'sla_paused_duration_minutes' => ($conversation->sla_paused_duration_minutes ?? 0) + $pausedMinutes,
            'sla_paused_at' => null,
        ];

        if ($conversation->sla_first_response_due_at) {
            $updates['sla_first_response_due_at'] = $conversation->sla_first_response_due_at->copy()->addMinutes($pausedMinutes);
        }

        if ($conversation->sla_resolution_due_at) {
            $updates['sla_resolution_due_at'] = $conversation->sla_resolution_due_at->copy()->addMinutes($pausedMinutes);
        }

        $conversation->updateQuietly($updates);
    }

    /**
     * Determine which SLA dimension is past the warning threshold, if any.
     * First response takes precedence over resolution.
     *
     * @return array{0: string, 1: int}|null [type, percentUsed]
     */
    private function approachingWarning(Conversation $conversation, int $threshold): ?array
    {
        $created = $conversation->created_at;

        if (! $created) {
            return null;
        }

        if (
            $conversation->first_response_at === null
            && ! $conversation->sla_first_response_breached
            && $conversation->sla_first_response_due_at?->isFuture()
        ) {
            $percent = $this->percentUsed($created, $conversation->sla_first_response_due_at);

            if ($percent >= $threshold) {
                return [ConversationSlaBreach::TYPE_FIRST_RESPONSE, $percent];
            }
        }

        if (
            ! $conversation->sla_resolution_breached
            && $conversation->sla_resolution_due_at?->isFuture()
        ) {
            $percent = $this->percentUsed($created, $conversation->sla_resolution_due_at);

            if ($percent >= $threshold) {
                return [ConversationSlaBreach::TYPE_RESOLUTION, $percent];
            }
        }

        return null;
    }

    private function percentUsed(Carbon $created, Carbon $due): int
    {
        $total = abs($created->diffInSeconds($due));

        if ($total <= 0) {
            return 100;
        }

        $used = abs($created->diffInSeconds(now()));

        return (int) min(100, round($used / $total * 100));
    }

    private function recordBreach(Conversation $conversation, string $type, ?Carbon $dueAt): ConversationSlaBreach
    {
        $minutesOver = $dueAt ? (int) round(abs($dueAt->diffInMinutes(now()))) : null;

        return ConversationSlaBreach::create([
            'conversation_id' => $conversation->id,
            'sla_type' => $type,
            'due_at' => $dueAt,
            'breached_at' => now(),
            'minutes_over' => $minutesOver,
        ]);
    }

    private function priorityIdForSlug(?string $slug): ?int
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $map = Cache::remember(self::PRIORITY_CACHE_KEY, 300, function (): array {
            return DB::connection('helpdesk')
                ->table('helpdesk_priorities')
                ->pluck('id', 'slug')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        });

        // Try the raw value first, then the English->Spanish slug alias.
        $alias = self::PRIORITY_SLUG_ALIASES[$slug] ?? null;

        return $map[$slug] ?? ($alias !== null ? ($map[$alias] ?? null) : null);
    }

    /**
     * Add a number of hours to a start date, optionally honouring the configured
     * business-hours calendar (helpdesk_business_hours, with a config fallback).
     */
    private function addBusinessHours(Carbon|string $start, int $hours, bool $businessHoursOnly): Carbon
    {
        $start = $start instanceof Carbon ? $start->copy() : Carbon::parse($start);

        if (! $businessHoursOnly || $hours <= 0) {
            return $start->copy()->addHours($hours);
        }

        $timezone = (string) config('helpdesksla.default_business_hours.timezone', 'Europe/Madrid');
        $schedule = $this->businessHoursSchedule();
        $cursor = $start->copy()->setTimezone($timezone);
        $remaining = $hours * 60;
        $guard = 0;

        while ($remaining > 0 && $guard++ < 1000) {
            $day = $schedule[$cursor->dayOfWeek] ?? null;

            if ($day === null) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }

            [$openHour, $openMinute] = array_map('intval', explode(':', $day['open']));
            [$closeHour, $closeMinute] = array_map('intval', explode(':', $day['close']));

            $open = $cursor->copy()->setTime($openHour, $openMinute);
            $close = $cursor->copy()->setTime($closeHour, $closeMinute);

            if ($cursor->lessThan($open)) {
                $cursor = $open->copy();
            }

            if ($cursor->greaterThanOrEqualTo($close)) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }

            $available = (int) round(abs($cursor->diffInMinutes($close)));

            if ($remaining <= $available) {
                $cursor = $cursor->addMinutes($remaining);
                $remaining = 0;
            } else {
                $remaining -= $available;
                $cursor = $cursor->addDay()->startOfDay();
            }
        }

        return $cursor->setTimezone($start->getTimezone());
    }

    /**
     * Business-hours calendar keyed by Carbon dayOfWeek (0=Sunday..6=Saturday).
     *
     * @return array<int, array{open: string, close: string}>
     */
    private function businessHoursSchedule(): array
    {
        return Cache::remember(self::BH_CACHE_KEY, 300, function (): array {
            $rows = BusinessHour::query()->where('is_open', true)->get();

            if ($rows->isEmpty()) {
                return config('helpdesksla.default_business_hours.days', []);
            }

            $map = [];

            foreach ($rows as $row) {
                if (! $row->opens_at || ! $row->closes_at) {
                    continue;
                }

                $map[(int) $row->day_of_week] = [
                    'open' => substr((string) $row->opens_at, 0, 5),
                    'close' => substr((string) $row->closes_at, 0, 5),
                ];
            }

            return $map;
        });
    }
}
