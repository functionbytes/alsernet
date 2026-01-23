<?php

namespace Modules\HelpdeskChat\Services;

use Carbon\Carbon;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Hour;

class HourService
{
    /**
     * Check if account is currently within business hours.
     */
    public function isWithinBusinessHours(Account $account, ?Inbox $inbox = null, ?Carbon $time = null): bool
    {
        $time = $time ?? Carbon::now();
        $dayOfWeek = $time->dayOfWeek;

        // Get business hours for this day
        $query = Hour::forAccount($account->id)
            ->enabled()
            ->where('day_of_week', $dayOfWeek);

        // If inbox is specified, prioritize inbox-specific hours
        if ($inbox) {
            $inboxHours = (clone $query)->forInbox($inbox->id)->first();
            if ($inboxHours) {
                return $inboxHours->isWithinBusinessHours($time);
            }
        }

        // Fall back to account-level hours
        $accountHours = $query->whereNull('inbox_id')->first();

        if (! $accountHours) {
            // No business hours configured, assume always open
            return true;
        }

        return $accountHours->isWithinBusinessHours($time);
    }

    /**
     * Get next available business time.
     */
    public function getNextAvailableTime(Account $account, ?Inbox $inbox = null, ?Carbon $time = null): ?Carbon
    {
        $time = $time ?? Carbon::now();
        $maxDays = 14; // Look up to 2 weeks ahead

        for ($i = 0; $i < $maxDays; $i++) {
            $checkTime = $time->copy()->addDays($i);
            $dayOfWeek = $checkTime->dayOfWeek;

            $query = Hour::forAccount($account->id)
                ->enabled()
                ->where('day_of_week', $dayOfWeek);

            if ($inbox) {
                $hours = (clone $query)->forInbox($inbox->id)->first()
                    ?? $query->whereNull('inbox_id')->first();
            } else {
                $hours = $query->whereNull('inbox_id')->first();
            }

            if ($hours) {
                $openTime = Carbon::parse($hours->open_time, $hours->timezone);
                $nextOpen = $checkTime->copy()->setTimeFrom($openTime);

                if ($nextOpen->greaterThan($time)) {
                    return $nextOpen;
                }
            }
        }

        return null;
    }

    /**
     * Create default business hours for an account (Monday-Friday, 9am-5pm).
     */
    public function createDefaultBusinessHours(Account $account, ?Inbox $inbox = null, string $timezone = 'UTC'): void
    {
        $weekdays = [
            Hour::MONDAY,
            Hour::TUESDAY,
            Hour::WEDNESDAY,
            Hour::THURSDAY,
            Hour::FRIDAY,
        ];

        foreach ($weekdays as $day) {
            Hour::create([
                'account_id' => $account->id,
                'inbox_id' => $inbox?->id,
                'day_of_week' => $day,
                'open_time' => '09:00:00',
                'close_time' => '17:00:00',
                'is_enabled' => true,
                'timezone' => $timezone,
            ]);
        }

        // Create disabled hours for weekends
        foreach ([Hour::SATURDAY, Hour::SUNDAY] as $day) {
            Hour::create([
                'account_id' => $account->id,
                'inbox_id' => $inbox?->id,
                'day_of_week' => $day,
                'open_time' => '09:00:00',
                'close_time' => '17:00:00',
                'is_enabled' => false,
                'timezone' => $timezone,
            ]);
        }
    }

    /**
     * Get formatted business hours summary.
     */
    public function getBusinessHoursSummary(Account $account, ?Inbox $inbox = null): array
    {
        $query = Hour::forAccount($account->id)->orderBy('day_of_week');

        if ($inbox) {
            $query->forInbox($inbox->id);
        } else {
            $query->whereNull('inbox_id');
        }

        $hours = $query->get();

        return $hours->map(function ($hour) {
            return [
                'day' => $hour->day_name,
                'open' => $hour->open_time,
                'close' => $hour->close_time,
                'enabled' => $hour->is_enabled,
                'timezone' => $hour->timezone,
            ];
        })->toArray();
    }
}
