<?php

namespace Modules\Backup\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'frequency' => $this->frequency,
            'time' => $this->scheduled_time?->format('H:i'),
            'isActive' => $this->enabled,
            'lastRunAt' => $this->last_run_at?->toIso8601String(),
            'nextRunAt' => $this->next_run_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
