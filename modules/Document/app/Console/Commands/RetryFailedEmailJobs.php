<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Document\Jobs\MailTemplateJob;

class RetryFailedEmailJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email-jobs:retry {--type= : Filter by error type} {--before= : Retry jobs failed before this date (Y-m-d H:i:s)} {--limit=20 : Max jobs to retry} {--dry-run : Show what would be retried without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed email jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $before = $this->option('before');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $query = DB::table('failed_jobs');

        if ($type) {
            $query->whereRaw('exception LIKE ?', ['%'.$type.'%']);
        }

        if ($before) {
            $query->where('failed_at', '<=', $before);
        }

        $failedJobs = $query->orderBy('failed_at', 'desc')->limit($limit)->get();

        if ($failedJobs->isEmpty()) {
            $this->info('No matching failed jobs found.');

            return 0;
        }

        $this->warn("Found {$failedJobs->count()} failed jobs to retry.");

        if (! $dryRun && ! $this->confirm('Retry these jobs?')) {
            $this->info('Cancelled.');

            return 0;
        }

        $retried = 0;
        $failed = 0;

        foreach ($failedJobs as $failedJob) {
            try {
                $payload = json_decode($failedJob->payload, true);

                if ($payload && isset($payload['data']['command'])) {
                    $command = unserialize($payload['data']['command']);

                    if ($dryRun) {
                        $this->line("Would retry job #{$failedJob->id}: {$failedJob->failed_at}");
                    } else {
                        // Delete from failed_jobs
                        DB::table('failed_jobs')->where('id', $failedJob->id)->delete();

                        // Re-dispatch the job
                        if ($command instanceof MailTemplateJob) {
                            MailTemplateJob::dispatch(
                                $command->document,
                                $command->emailType ?? 'request',
                                $command->emailData ?? [],
                                $command->adminId
                            )->onQueue('emails');

                            $this->line("Retried job #{$failedJob->id}");
                            $retried++;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Failed to retry job #{$failedJob->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        if (! $dryRun) {
            $this->newLine();
            $this->info("Retried: $retried | Failed: $failed");
        }

        return 0;
    }
}
