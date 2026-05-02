<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialSlaPolicy;

class SlaTrackingService
{
    /**
     * Find and apply the best matching SLA policy to a comment.
     */
    public function applyPolicyToComment(SocialComment $comment): ?SocialSlaPolicy
    {
        $policy = $this->findMatchingPolicy($comment);

        if ($policy === null) {
            return null;
        }

        $responseDeadline = $policy->response_time_minutes
            ? now()->addMinutes($policy->response_time_minutes)
            : null;

        $resolutionDeadline = $policy->resolution_time_minutes
            ? now()->addMinutes($policy->resolution_time_minutes)
            : null;

        $comment->update([
            'social_sla_policy_id' => $policy->id,
            'sla_response_deadline' => $responseDeadline,
            'sla_resolution_deadline' => $resolutionDeadline,
            'sla_response_breached' => false,
            'sla_resolution_breached' => false,
        ]);

        Log::info('SlaTrackingService: policy applied', [
            'comment_id' => $comment->id,
            'policy_id' => $policy->id,
            'policy_name' => $policy->name,
        ]);

        return $policy;
    }

    /**
     * Check if the first response SLA has been breached.
     */
    public function checkResponseBreach(SocialComment $comment): bool
    {
        if ($comment->first_response_at && $comment->sla_response_deadline) {
            $breached = $comment->first_response_at->gt($comment->sla_response_deadline);

            if ($breached && ! $comment->sla_response_breached) {
                $comment->update(['sla_response_breached' => true]);

                Log::warning('SlaTrackingService: response SLA breached', [
                    'comment_id' => $comment->id,
                    'deadline' => $comment->sla_response_deadline,
                    'first_response_at' => $comment->first_response_at,
                ]);
            }

            return $breached;
        }

        // If no first response yet, check if deadline has passed
        if (! $comment->first_response_at && $comment->sla_response_deadline) {
            $breached = now()->gt($comment->sla_response_deadline);

            if ($breached && ! $comment->sla_response_breached) {
                $comment->update(['sla_response_breached' => true]);

                Log::warning('SlaTrackingService: response SLA breached (no response)', [
                    'comment_id' => $comment->id,
                    'deadline' => $comment->sla_response_deadline,
                ]);
            }

            return $breached;
        }

        return false;
    }

    /**
     * Check if the resolution SLA has been breached.
     */
    public function checkResolutionBreach(SocialComment $comment): bool
    {
        if (! $comment->sla_resolution_deadline) {
            return false;
        }

        $resolvedAt = $comment->replied_at ?? $comment->updated_at ?? now();
        $breached = $resolvedAt->gt($comment->sla_resolution_deadline);

        if ($breached && ! $comment->sla_resolution_breached) {
            $comment->update(['sla_resolution_breached' => true]);

            Log::warning('SlaTrackingService: resolution SLA breached', [
                'comment_id' => $comment->id,
                'deadline' => $comment->sla_resolution_deadline,
                'resolved_at' => $resolvedAt,
            ]);
        }

        return $breached;
    }

    /**
     * Evaluate all open comments for SLA breaches.
     *
     * @return array<string, mixed>
     */
    public function evaluateAllOpenComments(): array
    {
        $comments = SocialComment::query()
            ->whereNotNull('social_sla_policy_id')
            ->whereIn('status', ['pending', 'escalated'])
            ->get();

        $responseBreaches = 0;
        $resolutionBreaches = 0;

        foreach ($comments as $comment) {
            if ($this->checkResponseBreach($comment)) {
                $responseBreaches++;
            }

            if ($this->checkResolutionBreach($comment)) {
                $resolutionBreaches++;
            }
        }

        return [
            'checked' => $comments->count(),
            'response_breaches' => $responseBreaches,
            'resolution_breaches' => $resolutionBreaches,
        ];
    }

    private function findMatchingPolicy(SocialComment $comment): ?SocialSlaPolicy
    {
        $policies = SocialSlaPolicy::active()
            ->orderByDesc('priority')
            ->get();

        foreach ($policies as $policy) {
            if ($this->policyMatches($comment, $policy)) {
                return $policy;
            }
        }

        return null;
    }

    private function policyMatches(SocialComment $comment, SocialSlaPolicy $policy): bool
    {
        $conditions = $policy->conditions ?? [];

        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $field => $expected) {
            $actual = match ($field) {
                'platform' => $comment->platform,
                'intent' => $comment->intent,
                'urgency' => $comment->urgency,
                'status' => $comment->status,
                'is_mention' => $comment->is_mention,
                default => null,
            };

            if (is_array($expected)) {
                if (! in_array($actual, $expected, true)) {
                    return false;
                }
            } elseif ($actual !== $expected) {
                return false;
            }
        }

        return true;
    }
}
