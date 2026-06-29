<?php

namespace Modules\Backup\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Backup\Http\Resources\BackupScheduleResource;
use Modules\Backup\Models\BackupSchedule;

class BackupScheduleApiController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $schedules = BackupSchedule::query()
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => BackupScheduleResource::collection($schedules->getCollection()),
            'meta' => [
                'currentPage' => $schedules->currentPage(),
                'lastPage' => $schedules->lastPage(),
                'perPage' => $schedules->perPage(),
                'total' => $schedules->total(),
            ],
        ]);
    }

    public function show(BackupSchedule $schedule): JsonResponse
    {
        return $this->apiSuccess(new BackupScheduleResource($schedule));
    }
}
