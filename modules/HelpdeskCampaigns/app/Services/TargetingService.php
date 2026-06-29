<?php

namespace Modules\HelpdeskCampaigns\Services;

use Modules\HelpdeskCampaigns\Models\Campaign;

/**
 * Evaluates a campaign's `conditions` JSON against a visitor context to
 * decide whether the campaign matches. Conditions schema (all optional):
 *
 *   {
 *     "url_match": "/checkout/*",            // glob
 *     "device_types": ["desktop", "mobile"],
 *     "countries": ["ES", "PT"],
 *     "segments": [12, 34],                  // Engagement segment IDs
 *     "min_visits": 3,                       // VisitorScore threshold
 *     "languages": ["es", "ca"],
 *     "show_after_seconds": 5,               // delay (handled in JS)
 *     "scroll_percent": 50                   // delay (handled in JS)
 *   }
 *
 * Engagement-related conditions (segments, min_visits) are evaluated only when
 * the Engagement module is loaded. Otherwise they are silently skipped (the
 * campaign matches without enforcing them).
 */
class TargetingService
{
    public function matches(Campaign $campaign, array $visitor): bool
    {
        $conditions = $campaign->conditions ?? [];

        if (empty($conditions)) {
            return true;
        }

        if (! $this->matchesUrl($conditions, $visitor)) {
            return false;
        }
        if (! $this->matchesDevice($conditions, $visitor)) {
            return false;
        }
        if (! $this->matchesCountry($conditions, $visitor)) {
            return false;
        }
        if (! $this->matchesLanguage($conditions, $visitor)) {
            return false;
        }
        if (! $this->matchesSegments($conditions, $visitor)) {
            return false;
        }

        return true;
    }

    private function matchesUrl(array $conditions, array $visitor): bool
    {
        if (empty($conditions['url_match'])) {
            return true;
        }

        $pattern = $conditions['url_match'];
        $url = $visitor['page_url'] ?? '';

        return fnmatch($pattern, $url);
    }

    private function matchesDevice(array $conditions, array $visitor): bool
    {
        $allowed = $conditions['device_types'] ?? null;

        if (empty($allowed)) {
            return true;
        }

        return in_array($visitor['device_type'] ?? null, $allowed, true);
    }

    private function matchesCountry(array $conditions, array $visitor): bool
    {
        $allowed = $conditions['countries'] ?? null;

        if (empty($allowed)) {
            return true;
        }

        return in_array(strtoupper($visitor['country'] ?? ''), array_map('strtoupper', $allowed), true);
    }

    private function matchesLanguage(array $conditions, array $visitor): bool
    {
        $allowed = $conditions['languages'] ?? null;

        if (empty($allowed)) {
            return true;
        }

        $lang = strtolower(substr($visitor['language'] ?? '', 0, 2));

        return in_array($lang, array_map('strtolower', $allowed), true);
    }

    /**
     * Engagement-driven targeting. Skips silently if Engagement is not installed.
     */
    private function matchesSegments(array $conditions, array $visitor): bool
    {
        $segmentIds = $conditions['segments'] ?? null;

        if (empty($segmentIds) || empty($visitor['customer_id'])) {
            return true;
        }

        $segmentClass = '\\Modules\\Engagement\\Models\\Segment';

        if (! class_exists($segmentClass)) {
            return true; // Engagement not installed — fail open
        }

        return $segmentClass::query()
            ->whereIn('id', $segmentIds)
            ->whereHas('customers', fn ($q) => $q->where('helpdesk_customers.id', $visitor['customer_id']))
            ->exists();
    }
}
