<?php

namespace Modules\Remarketing\Services;

use Illuminate\Support\Collection;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Models\VisitorScore;
use Nwidart\Modules\Facades\Module;

/**
 * Bridge to consume engagement data when the Engagement module is active.
 * If Engagement is disabled, all methods return empty collections — Remarketing
 * works on its own data sources (orders, customers, carts).
 */
class EngagementBridgeService
{
    public function isAvailable(): bool
    {
        return class_exists(Event::class)
            && Module::find('Engagement')?->isEnabled();
    }

    /**
     * Get visitor score for a session token (0-100).
     */
    public function getVisitorScore(string $sessionToken): ?int
    {
        if (! $this->isAvailable()) {
            return null;
        }

        return VisitorScore::query()
            ->where('session_token', $sessionToken)
            ->value('score');
    }

    /**
     * Get visitor segment ('cold', 'warm', 'hot') for session.
     */
    public function getVisitorSegment(string $sessionToken): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        return VisitorScore::query()
            ->where('session_token', $sessionToken)
            ->value('segment');
    }

    /**
     * Get hot visitors (score >= 60) within last N hours, with their customer ID.
     * Used to build remarketing segments dynamically.
     */
    public function hotVisitorsLast(int $hours = 24): Collection
    {
        if (! $this->isAvailable()) {
            return collect();
        }

        return VisitorScore::query()
            ->where('segment', 'hot')
            ->whereNotNull('customer_id')
            ->where('updated_at', '>=', now()->subHours($hours))
            ->get(['customer_id', 'session_token', 'score', 'segment', 'updated_at']);
    }

    /**
     * Customers who triggered a specific event in the last N hours.
     * Useful for cart abandonment campaigns ('add_to_cart' without 'purchase').
     */
    public function customersWithEvent(string $eventName, int $hours = 24): Collection
    {
        if (! $this->isAvailable()) {
            return collect();
        }

        return Event::query()
            ->where('event_name', $eventName)
            ->whereNotNull('customer_id')
            ->where('occurred_at', '>=', now()->subHours($hours))
            ->distinct('customer_id')
            ->pluck('customer_id');
    }

    /**
     * Customers who abandoned cart (have add_to_cart but no purchase in window).
     */
    public function customersWhoAbandonedCart(int $hoursWindow = 24): Collection
    {
        if (! $this->isAvailable()) {
            return collect();
        }

        $eventClass = Event::class;
        $since = now()->subHours($hoursWindow);

        $addedToCart = $eventClass::query()
            ->where('event_name', 'add_to_cart')
            ->where('occurred_at', '>=', $since)
            ->whereNotNull('customer_id')
            ->pluck('customer_id')
            ->unique();

        $purchased = $eventClass::query()
            ->where('event_name', 'purchase')
            ->where('occurred_at', '>=', $since)
            ->whereNotNull('customer_id')
            ->pluck('customer_id')
            ->unique();

        return $addedToCart->diff($purchased)->values();
    }

    /**
     * UTM attribution for a session — used to attribute conversions.
     */
    public function getAttribution(string $sessionToken): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $context = VisitorContext::query()
            ->where('session_token', $sessionToken)
            ->first(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'referrer_host']);

        return $context?->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'referrer_host']);
    }
}
