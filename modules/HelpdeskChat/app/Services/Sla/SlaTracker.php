<?php

namespace Modules\HelpdeskChat\Services\Sla;

use App\Models\SlaAppliedConversation;
use Carbon\Carbon;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class SlaTracker
{
    /**
     * Create or update SLA tracking for a conversation.
     */
    public function trackConversation(Conversation $conversation): ?SlaAppliedConversation
    {
        // If conversation doesn't have an SLA policy, skip
        if (! $conversation->sla_policy_id) {
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
        $slaTracking->sla_policy_id = $policy->id;

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
     * Record first response time for a conversation.
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
     * Record resolution time for a conversation.
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
     * Check for SLA breaches across all active conversation.
     * Returns count of newly breached SLAs.
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
        }

        return $breachCount;
    }

    /**
     * Calculate due date based on minutes and business hours setting.
     *
     * @param  Carbon  $startTime  The start time for the SLA
     * @param  int  $minutes  The number of minutes for the SLA
     * @param  bool  $businessHoursOnly  Whether to calculate only during business hours
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
     * Get SLA statistics for an account.
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
