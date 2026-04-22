<?php

namespace Modules\Helpdesk\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Models\AgentShift;
use Modules\Helpdesk\Models\AgentVacation;

class AgentAvailabilityService
{
    public function isAgentAvailableNow(int $userId): bool
    {
        if ($this->isOnVacation($userId)) {
            return false;
        }

        return $this->hasActiveShiftNow($userId);
    }

    public function availableAgents(): Collection
    {
        $today = now()->dayOfWeek;
        $currentTime = now()->format('H:i:s');

        $availableUserIds = AgentShift::query()
            ->where('day_of_week', $today)
            ->where('is_active', true)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->pluck('user_id')
            ->unique();

        $onVacationUserIds = AgentVacation::query()
            ->where('status', 'approved')
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now())
            ->pluck('user_id');

        $finalIds = $availableUserIds->diff($onVacationUserIds);

        return User::whereIn('id', $finalIds)->get();
    }

    private function isOnVacation(int $userId): bool
    {
        return AgentVacation::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now())
            ->exists();
    }

    private function hasActiveShiftNow(int $userId): bool
    {
        return AgentShift::query()
            ->where('user_id', $userId)
            ->where('day_of_week', now()->dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '<=', now()->format('H:i:s'))
            ->where('end_time', '>=', now()->format('H:i:s'))
            ->exists();
    }
}
