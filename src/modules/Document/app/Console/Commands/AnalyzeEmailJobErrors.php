<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeEmailJobErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email-jobs:analyze-errors {--limit=20 : Number of errors to show} {--type= : Filter by error type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze email job failures and group by error type';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $typeFilter = $this->option('type');

        $this->info('=== Email Job Error Analysis ===');
        $this->newLine();

        // Get failed jobs
        $query = DB::table('failed_jobs')->orderBy('failed_at', 'desc');

        if ($typeFilter) {
            $query->whereRaw('exception LIKE ?', ['%'.$typeFilter.'%']);
        }

        $failedJobs = $query->limit($limit)->get();

        if ($failedJobs->isEmpty()) {
            $this->info('No failed jobs found.');

            return 0;
        }

        // Group errors by type
        $errorGroups = [];

        foreach ($failedJobs as $job) {
            if (preg_match('/^([^\:]+):/', $job->exception, $matches)) {
                $errorType = $matches[1];
            } else {
                $errorType = 'Unknown Error';
            }

            if (! isset($errorGroups[$errorType])) {
                $errorGroups[$errorType] = [];
            }

            // Extract error message
            if (preg_match('/:\s+(.+?)\s+in\s+/', $job->exception, $msgMatches)) {
                $errorMsg = $msgMatches[1];
            } else {
                $errorMsg = 'No message';
            }

            $errorGroups[$errorType][] = [
                'id' => $job->id,
                'message' => substr($errorMsg, 0, 100),
                'failed_at' => $job->failed_at,
                'full_exception' => $job->exception,
            ];
        }

        // Display summary
        $this->line('Error Summary:');
        $this->table(
            ['Error Type', 'Count'],
            collect($errorGroups)->map(fn ($errors, $type) => [$type, count($errors)])->toArray()
        );

        $this->newLine();

        // Display details for each error type
        foreach ($errorGroups as $errorType => $errors) {
            $count = count($errors);
            $this->warn("\n=== $errorType ($count occurrences) ===");

            foreach ($errors as $error) {
                $this->line("ID: {$error['id']} | Failed: {$error['failed_at']}");
                $this->line("Message: {$error['message']}");

                if ($this->confirm('Show full exception for job #'.$error['id'].'?', false)) {
                    $this->line($error['full_exception']);
                }

                $this->newLine();
            }
        }

        $this->info('Detailed logs available in: storage/logs/email-jobs.log');

        return 0;
    }
}
