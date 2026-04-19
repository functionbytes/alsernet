<?php

namespace Modules\Backup\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Backup\Models\BackupSchedule;

class BackupScheduleApiController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $schedules = BackupSchedule::query()
            ->select(['id', 'name', 'enabled', 'frequency', 'scheduled_time', 'last_run_at', 'next_run_at'])
            ->orderBy('name')
            ->paginate(20);

        return $this->paginated($schedules);
    }

    public function show(BackupSchedule $schedule): JsonResponse
    {
        return $this->apiSuccess($schedule->only([
            'id', 'name', 'enabled', 'frequency', 'scheduled_time',
            'days_of_week', 'days_of_month', 'custom_interval_hours',
            'backup_types', 'last_run_at', 'next_run_at',
        ]));
    }
}
