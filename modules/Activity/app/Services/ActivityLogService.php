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
            $row = Activity::query()
                ->selectRaw("COUNT(*) as total, SUM(event='created') as created, SUM(event='updated') as updated, SUM(event='deleted') as deleted")
                ->first();

            return [
                'created' => (int) $row->created,
                'updated' => (int) $row->updated,
                'deleted' => (int) $row->deleted,
                'total' => (int) $row->total,
            ];
        });
    }
}
