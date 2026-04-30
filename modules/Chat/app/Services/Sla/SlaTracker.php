<?php

namespace Modules\Chat\Services\Sla;

use Carbon\Carbon;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Sla\SlaAppliedConversation;

class SlaTracker
{
    /**
     * Create or update SLA tracking for a conversation.
     *
     * Initializes SLA tracking if conversation has an associated SLA policy.
     * Calculates first response and resolution due dates based on policy settings
     * and business hours configuration. Idempotent - won't recalculate dates if already set.
     *
     * @param  Conversation  $conversation  The conversation to track
     * @return SlaAppliedConversation|null Created/updated SLA tracking record, or null if no policy
     */
    public function trackConversation(Conversation $conversation): ?SlaAppliedConversation
    {
        // If conversation doesn't have an SLA policy, skip
        if (! $conversation->sla_id) {
            return null;
        }

        $policy = $conversation->slaPolicy;

        if (! $policy) {
            return null;
        }

        // Get or create SLA tracking record
        $slaTracking = $conversation->slaTracking()->firstOrNew([
            'conversation_id' => $conversation->id,
        ]);

        // Set the policy
        $slaTracking->sla_id = $policy->id;

        // Calculate first response due date if not already set
        if (! $slaTracking->first_response_due_at && $policy->first_response_time_minutes) {
            $slaTracking->first_response_due_at = $this->calculateDueDate(
                $conversation->created_at,
                $policy->first_response_time_minutes,
                $policy->business_hours_only
            );
        }

        // Calculate resolution due date if not already set
        if (! $slaTracking->resolution_due_at && $policy->resolution_time_minutes) {
            $slaTracking->resolution_due_at = $this->calculateDueDate(
                $conversation->created_at,
                $policy->resolution_time_minutes,
                $policy->business_hours_only
            );
        }

        $slaTracking->save();

        return $slaTracking;
    }

    /**
     * Record the first response time for a conversation.
     *
     * Updates first_response_at timestamp and sets first_response_breached flag
     * if response time exceeds the due date. Idempotent - won't update if already recorded.
     *
     * @param  Conversation  $conversation  The conversation to record first response for
     */
    public function recordFirstResponse(Conversation $conversation): void
    {
        $slaTracking = $conversation->slaTracking;

        if (! $slaTracking || $slaTracking->first_response_at) {
            return;
        }

        $now = now();
        $slaTracking->first_response_at = $now;

        // Check if breached
        if ($slaTracking->first_response_due_at && $now->isAfter($slaTracking->first_response_due_at)) {
            $slaTracking->first_response_breached = true;
        }

        $slaTracking->save();
    }

    /**
     * Record the resolution time for a conversation.
     *
     * Updates resolved_at timestamp and sets resolution_breached flag if resolution
     * time exceeds the due date. Idempotent - won't update if already recorded.
     *
     * @param  Conversation  $conversation  The conversation to record resolution for
     */
    public function recordResolution(Conversation $conversation): void
    {
        $slaTracking = $conversation->slaTracking;

        if (! $slaTracking || $slaTracking->resolved_at) {
            return;
        }

        $now = now();
        $slaTracking->resolved_at = $now;

        // Check if breached
        if ($slaTracking->resolution_due_at && $now->isAfter($slaTracking->resolution_due_at)) {
            $slaTracking->resolution_breached = true;
        }

        $slaTracking->save();
    }

    /**
     * Check for and mark SLA breaches across all active conversations.
     *
     * Scans all unresolved SLA-tracked conversations and marks those that have
     * exceeded their due dates. Returns count of newly breached SLAs detected.
     * Should be run periodically as a scheduled job.
     *
     * @return int Number of newly breached SLAs
     */
    public function checkBreaches(): int
    {
        $now = now();
        $breachCount = 0;

        // Check first response breaches
        $firstResponseBreaches = SlaAppliedConversation::query()
            ->whereNull('first_response_at')
            ->whereNotNull('first_response_due_at')
            ->where('first_response_due_at', '<', $now)
            ->where('first_response_breached', false)
            ->get();

        foreach ($firstResponseBreaches as $sla) {
            $sla->update(['first_response_breached' => true]);
            $breachCount++;

            // Log SLA breach
            $conversation = $sla->conversation;
            $policy = $sla->slaPolicy;

            activity()
                ->performedOn($conversation)
                ->withProperties([
                    'sla_policy_id' => $policy?->id,
                    'sla_policy_name' => $policy?->name,
                    'breach_type' => 'first_response',
                    'due_at' => $sla->first_response_due_at?->toDateTimeString(),
                    'breached_at' => $now->toDateTimeString(),
                    'conversation_id' => $conversation->id,
                ])
                ->log('sla_breach_detected');
        }

        // Check resolution breaches
        $resolutionBreaches = SlaAppliedConversation::query()
            ->whereNull('resolved_at')
            ->whereNotNull('resolution_due_at')
            ->where('resolution_due_at', '<', $now)
            ->where('resolution_breached', false)
            ->get();

        foreach ($resolutionBreaches as $sla) {
            $sla->update(['resolution_breached' => true]);
            $breachCount++;

            // Log SLA breach
            $conversation = $sla->conversation;
            $policy = $sla->slaPolicy;

            activity()
                ->performedOn($conversation)
                ->withProperties([
                    'sla_policy_id' => $policy?->id,
                    'sla_policy_name' => $policy?->name,
                    'breach_type' => 'resolution',
                    'due_at' => $sla->resolution_due_at?->toDateTimeString(),
                    'breached_at' => $now->toDateTimeString(),
                    'conversation_id' => $conversation->id,
                ])
                ->log('sla_breach_detected');
        }

        return $breachCount;
    }

    /**
     * Calculate SLA due date based on minutes and business hours setting.
     *
     * If businessHoursOnly is true, calculates within business hours (9 AM - 5 PM, Mon-Fri).
     * Skips weekends and after-hours periods. Otherwise adds minutes directly.
     *
     * @param  Carbon  $startTime  The start time for the SLA calculation
     * @param  int  $minutes  The number of minutes for the SLA duration
     * @param  bool  $businessHoursOnly  Whether to skip non-business hours (default: false)
     * @return Carbon The calculated due date and time
     */
    protected function calculateDueDate(Carbon $startTime, int $minutes, bool $businessHoursOnly = false): Carbon
    {
        if (! $businessHoursOnly) {
            // Simple calculation: just add minutes
            return $startTime->copy()->addMinutes($minutes);
        }

        // Business hours calculation
        // Assuming standard business hours: 9 AM - 5 PM, Monday to Friday
        $current = $startTime->copy();
        $remainingMinutes = $minutes;

        while ($remainingMinutes > 0) {
            // Skip weekends
            if ($current->isWeekend()) {
                $current->addDay()->setTime(9, 0, 0);

                continue;
            }

            // If before business hours (before 9 AM), jump to 9 AM
            if ($current->hour < 9) {
                $current->setTime(9, 0, 0);

                continue;
            }

            // If after business hours (after 5 PM), jump to next day 9 AM
            if ($current->hour >= 17) {
                $current->addDay()->setTime(9, 0, 0);

                continue;
            }

            // We're in business hours - calculate remaining minutes for today
            $endOfDay = $current->copy()->setTime(17, 0, 0);
            $minutesLeftToday = $current->diffInMinutes($endOfDay);

            if ($remainingMinutes <= $minutesLeftToday) {
                // Can finish today
                $current->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                // Use up today's business hours and continue tomorrow
                $remainingMinutes -= $minutesLeftToday;
                $current->addDay()->setTime(9, 0, 0);
            }
        }

        return $current;
    }

    /**
     * Get SLA compliance statistics for an account.
     *
     * Returns total SLA-tracked conversations, counts by status (on-track, approaching,
     * breached), and overall compliance rate percentage.
     *
     * @param  int  $accountId  The account to get statistics for
     * @return array{total: int, on_track: int, approaching: int, breached: int, compliance_rate: float}
     */
    public function getAccountStatistics(int $accountId): array
    {
        $total = SlaAppliedConversation::forAccount($accountId)->count();
        $breached = SlaAppliedConversation::forAccount($accountId)->breached()->count();
        $approaching = SlaAppliedConversation::forAccount($accountId)->approaching()->count();
        $onTrack = $total - $breached - $approaching;

        $complianceRate = $total > 0 ? round((($total - $breached) / $total) * 100, 2) : 100;

        return [
            'total' => $total,
            'on_track' => $onTrack,
            'approaching' => $approaching,
            'breached' => $breached,
            'compliance_rate' => $complianceRate,
        ];
    }
}
