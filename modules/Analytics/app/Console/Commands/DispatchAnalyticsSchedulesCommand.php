<?php

namespace Modules\Analytics\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\Analytics\Jobs\GenerateAnalyticsReport;
use Modules\Analytics\Models\AnalyticsReportSchedule;
use Modules\Analytics\Services\AnalyticsReportService;

class DispatchAnalyticsSchedulesCommand extends Command
{
    protected $signature = 'analytics:dispatch-schedules';

    protected $description = 'Despacha jobs de reportes analytics cuyo next_run_at ya venció';

    public function __construct(private readonly AnalyticsReportService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $lock = Cache::lock('analytics:dispatch-schedules', 60);

        if (! $lock->get()) {
            $this->warn('Another instance is already running.');

            return self::SUCCESS;
        }

        try {
            return $this->dispatch();
        } finally {
            $lock->release();
        }
    }

    private function dispatch(): int
    {
        $schedules = AnalyticsReportSchedule::query()->due()->get();

        if ($schedules->isEmpty()) {
            $this->info(__('analytics::analytics.schedules.no_pending'));

            return self::SUCCESS;
        }

        $this->info(__('analytics::analytics.schedules.dispatching', ['count' => $schedules->count()]));

        foreach ($schedules as $schedule) {
            GenerateAnalyticsReport::dispatchForSchedule($schedule);

            $schedule->update(['next_run_at' => $this->service->calculateNextRun($schedule->frequency)]);

            $this->line("  -> [{$schedule->frequency}] {$schedule->name} -> {$schedule->email}");
        }

        return self::SUCCESS;
    }
}
