<?php

namespace Modules\Health\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\Health\Notifications\HealthCheckFailedNotification;
use Spatie\Health\Facades\Health;

class HealthCheckAlertCommand extends Command
{
    protected $signature = 'health:check-and-alert';

    protected $description = 'Run all health checks and notify admins when checks fail or warn';

    public function handle(): int
    {
        $checks = Health::registeredChecks();

        foreach ($checks as $check) {
            $result = $check->run();
            $status = $result->status->value;

            if (! in_array($status, ['warning', 'failed'])) {
                continue;
            }

            $checkName = $check->getName();
            $cacheKey = "health_alert:{$checkName}";

            if (Cache::has($cacheKey)) {
                continue;
            }

            $this->notifyAdmins($checkName, $status, $result->shortSummary ?? '');

            Cache::put($cacheKey, true, now()->addHour());

            $this->line("Alert sent for check: {$checkName} ({$status})");
        }

        return self::SUCCESS;
    }

    private function notifyAdmins(string $checkName, string $status, string $summary): void
    {
        $notification = new HealthCheckFailedNotification(
            checkName: $checkName,
            status: $status,
            summary: $summary,
            checkedAt: now(),
        );

        User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->each(fn (User $user) => $user->notify($notification));
    }
}
