<?php

namespace Modules\Analytics\Console\Commands;

use Modules\Analytics\Jobs\GenerateAnalyticsReport;
use Illuminate\Console\Command;

class GenerateAnalyticsReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:report
                            {--type=daily : Type of report (daily, weekly, monthly)}
                            {--email= : Email to send the report to}
                            {--queue : Queue the job instead of executing synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate analytics report for specified period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $email = $this->option('email');
        $shouldQueue = $this->option('queue');

        // Validar tipo de reporte
        if (!in_array($type, ['daily', 'weekly', 'monthly'])) {
            $this->error("Invalid report type: {$type}. Must be: daily, weekly, or monthly");
            return 1;
        }

        try {
            $this->info("Generating {$type} analytics report...");

            $job = new GenerateAnalyticsReport($type, $email);

            if ($shouldQueue) {
                dispatch($job);
                $this->info('✓ Report generation job queued successfully');
            } else {
                $job->handle();
                $this->info('✓ Report generated successfully');
            }

            if ($email) {
                $this->info("✓ Report will be sent to: {$email}");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to generate report: ' . $e->getMessage());
            return 1;
        }
    }
}
