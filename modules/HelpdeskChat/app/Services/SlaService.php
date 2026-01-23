<?php

namespace Modules\HelpdeskChat\Services;

use App\Models\SlaPolicy;
use Carbon\Carbon;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class SlaService
{
    public function __construct(
        protected HourService $businessHourService
    ) {}

    /**
     * Check and update SLA status for a conversation after first response.
     */
    public function checkFirstResponse(Conversation $conversation): void
    {
        if (! $conversation->sla_policy_id || $conversation->first_response_at) {
            return;
        }

        // Check if there's an outgoing message from an agent
        $firstResponse = $conversation->messages()
            ->where('message_type', 'outgoing')
            ->where('sender_type', \App\Models\User::class)
            ->orderBy('created_at')
            ->first();

        if (! $firstResponse) {
            return;
        }

        $conversation->update([
            'first_response_at' => $firstResponse->created_at,
        ]);

        $this->checkFirstResponseBreach($conversation);
    }

    /**
     * Check if first response SLA was breached.
     */
    protected function checkFirstResponseBreach(Conversation $conversation): void
    {
        if (! $conversation->sla_policy_id || ! $conversation->first_response_at) {
            return;
        }

        $policy = $conversation->slaPolicy;

        if (! $policy || ! $policy->first_response_time_minutes) {
            return;
        }

        $elapsedMinutes = $this->calculateElapsedMinutes(
            $conversation->created_at,
            $conversation->first_response_at,
            $policy->business_hours_only,
            $conversation->account,
            $conversation->inbox
        );

        if ($elapsedMinutes > $policy->first_response_time_minutes) {
            $conversation->update(['first_response_sla_breached' => true]);

            // Notify assignee if conversation is assigned
            if ($conversation->assignee) {
                $conversation->assignee->notify(
                    new \App\Notifications\SlaBreachNotification($conversation, 'first_response')
                );
            }
        }
    }

    /**
     * Check and update SLA status when conversation is resolved.
     */
    public function checkResolution(Conversation $conversation): void
    {
        if (! $conversation->sla_policy_id || $conversation->status !== 'resolved') {
            return;
        }

        if (! $conversation->resolved_at) {
            $conversation->update(['resolved_at' => now()]);
        }

        $this->checkResolutionBreach($conversation);
    }

    /**
     * Check if resolution SLA was breached.
     */
    protected function checkResolutionBreach(Conversation $conversation): void
    {
        if (! $conversation->sla_policy_id || ! $conversation->resolved_at) {
            return;
        }

        $policy = $conversation->slaPolicy;

        if (! $policy || ! $policy->resolution_time_minutes) {
            return;
        }

        $elapsedMinutes = $this->calculateElapsedMinutes(
            $conversation->created_at,
            $conversation->resolved_at,
            $policy->business_hours_only,
            $conversation->account,
            $conversation->inbox
        );

        if ($elapsedMinutes > $policy->resolution_time_minutes) {
            $conversation->update(['resolution_sla_breached' => true]);

            // Notify assignee if conversation is assigned
            if ($conversation->assignee) {
                $conversation->assignee->notify(
                    new \App\Notifications\SlaBreachNotification($conversation, 'resolution')
                );
            }
        }
    }

    /**
     * Calculate elapsed minutes between two timestamps.
     */
    protected function calculateElapsedMinutes(
        Carbon $start,
        Carbon $end,
        bool $businessHoursOnly,
        $account,
        $inbox = null
    ): int {
        if (! $businessHoursOnly) {
            return $start->diffInMinutes($end);
        }

        // Calculate only business hours
        // This is a simplified version - a full implementation would iterate through each hour
        $totalMinutes = 0;
        $current = $start->copy();

        while ($current < $end) {
            if ($this->businessHourService->isWithinBusinessHours($account, $inbox, $current)) {
                $totalMinutes++;
            }
            $current->addMinute();

            // Optimization: if difference is too large, use estimated calculation
            if ($start->diffInMinutes($end) > 10080) { // More than a week
                // Rough estimate: assume 8 hours/day, 5 days/week
                return (int) ($start->diffInMinutes($end) * 0.238); // ~40/168 hours
            }
        }

        return $totalMinutes;
    }

    /**
     * Get SLA status summary for a conversation.
     */
    public function getSlaStatus(Conversation $conversation): array
    {
        if (! $conversation->sla_policy_id) {
            return [
                'has_policy' => false,
                'policy_name' => null,
                'first_response' => null,
                'resolution' => null,
            ];
        }

        $policy = $conversation->slaPolicy;

        return [
            'has_policy' => true,
            'policy_name' => $policy->name,
            'first_response' => $this->getFirstResponseStatus($conversation, $policy),
            'resolution' => $this->getResolutionStatus($conversation, $policy),
        ];
    }

    /**
     * Get first response SLA status.
     */
    protected function getFirstResponseStatus(Conversation $conversation, SlaPolicy $policy): ?array
    {
        if (! $policy->first_response_time_minutes) {
            return null;
        }

        $target = $conversation->created_at->copy()->addMinutes($policy->first_response_time_minutes);

        if ($conversation->first_response_at) {
            $elapsed = $conversation->created_at->diffInMinutes($conversation->first_response_at);

            return [
                'completed' => true,
                'breached' => $conversation->first_response_sla_breached,
                'target_minutes' => $policy->first_response_time_minutes,
                'elapsed_minutes' => $elapsed,
                'target_time' => $target,
                'completed_at' => $conversation->first_response_at,
            ];
        }

        $elapsed = $conversation->created_at->diffInMinutes(now());
        $remaining = max(0, $policy->first_response_time_minutes - $elapsed);

        return [
            'completed' => false,
            'breached' => $elapsed > $policy->first_response_time_minutes,
            'target_minutes' => $policy->first_response_time_minutes,
            'elapsed_minutes' => $elapsed,
            'remaining_minutes' => $remaining,
            'target_time' => $target,
        ];
    }

    /**
     * Get resolution SLA status.
     */
    protected function getResolutionStatus(Conversation $conversation, SlaPolicy $policy): ?array
    {
        if (! $policy->resolution_time_minutes) {
            return null;
        }

        $target = $conversation->created_at->copy()->addMinutes($policy->resolution_time_minutes);

        if ($conversation->resolved_at) {
            $elapsed = $conversation->created_at->diffInMinutes($conversation->resolved_at);

            return [
                'completed' => true,
                'breached' => $conversation->resolution_sla_breached,
                'target_minutes' => $policy->resolution_time_minutes,
                'elapsed_minutes' => $elapsed,
                'target_time' => $target,
                'completed_at' => $conversation->resolved_at,
            ];
        }

        $elapsed = $conversation->created_at->diffInMinutes(now());
        $remaining = max(0, $policy->resolution_time_minutes - $elapsed);

        return [
            'completed' => false,
            'breached' => $elapsed > $policy->resolution_time_minutes,
            'target_minutes' => $policy->resolution_time_minutes,
            'elapsed_minutes' => $elapsed,
            'remaining_minutes' => $remaining,
            'target_time' => $target,
        ];
    }
}
