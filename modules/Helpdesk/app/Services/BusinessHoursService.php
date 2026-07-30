<?php

namespace Modules\Helpdesk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\BusinessHour;

class BusinessHoursService
{
    private const CACHE_TTL = 60; // seconds

    /**
     * Determine if the business is open right now based on stored hours.
     * Returns true when open, false when outside hours or no records found.
     * Result is cached for 1 minute to avoid repeated DB queries on every webhook.
     */
    public function isOpenNow(): bool
    {
        return Cache::remember('helpdesk:business_hours_open', self::CACHE_TTL, function () {
            $hour = BusinessHour::query()
                ->where('day_of_week', now()->dayOfWeek)
                ->first();

            if (! $hour || ! $hour->is_open) {
                return false;
            }

            $timezone = $hour->timezone ?? 'Europe/Madrid';

            try {
                $now = Carbon::now($timezone);
            } catch (\Throwable) {
                $now = Carbon::now('Europe/Madrid');
            }

            $opens = Carbon::parse($now->format('Y-m-d').' '.$hour->opens_at, $timezone);
            $closes = Carbon::parse($now->format('Y-m-d').' '.$hour->closes_at, $timezone);

            return $now->between($opens, $closes);
        });
    }

    /**
     * Invalidate the cached open/closed state.
     */
    public function forgetCache(): void
    {
        Cache::forget('helpdesk:business_hours_open');
    }
}
