<?php

namespace Modules\Activity\Services;

use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    /**
     * Count activity log entries grouped by event.
     *
     * @return array{created: int, updated: int, deleted: int, total: int}
     */
    public function countByEvent(): array
    {
        return Cache::remember('activity:count-by-event', 300, function () {
            $counts = Activity::query()
                ->selectRaw('event, COUNT(*) as count')
                ->whereNotNull('event')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->all();

            $total = Activity::query()->count();

            return [
                'created' => (int) ($counts['created'] ?? 0),
                'updated' => (int) ($counts['updated'] ?? 0),
                'deleted' => (int) ($counts['deleted'] ?? 0),
                'total' => $total,
            ];
        });
    }
}
