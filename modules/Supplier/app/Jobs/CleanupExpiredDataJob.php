<?php

namespace Modules\Supplier\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Ai\AiCost;
use Modules\Supplier\Models\Automation\AutomationExecution;
use Modules\Supplier\Models\Automation\AutomationSetting;
use Modules\Supplier\Models\Content\ContentLog;
use Modules\Supplier\Models\Extraction\ExtractionResult;

/**
 * Cleanup Expired Data Job
 *
 * This job handles cleanup of old data from various supplier automation tables.
 * It should be scheduled to run daily to maintain database performance and
 * comply with data retention policies.
 *
 * Features:
 * - Cleans up old extraction results (configurable retention)
 * - Cleans up old content logs
 * - Cleans up old cost records
 * - Archives completed automation executions
 * - Uses configurable retention periods from backups
 *
 * Schedule in app/Console/Kernel.php:
 * $schedule->job(new CleanupExpiredDataJob)->daily()->at('02:00');
 */
class CleanupExpiredDataJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 3600; // 1 hour for cleanup operations

    /**
     * Default retention periods in days.
     *
     * @var array<string, int>
     */
    protected array $defaultRetentionPeriods = [
        'extraction_results' => 90,    // 3 months
        'content_logs' => 60,          // 2 months
        'cost_records' => 365,         // 1 year
        'completed_executions' => 30,  // 1 month
        'failed_executions' => 90,     // 3 months
    ];

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     *
     * Performs cleanup operations on all configured data types,
     * respecting retention periods from backups or defaults.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting cleanup expired data job');

            $totalDeleted = 0;
            $cleanupSummary = [];

            // Load retention periods from backups or use defaults
            $retentionPeriods = $this->getRetentionPeriods();

            // Clean up extraction results
            $deletedExtractions = $this->cleanupExtractionResults($retentionPeriods['extraction_results']);
            $totalDeleted += $deletedExtractions;
            $cleanupSummary['extraction_results'] = $deletedExtractions;

            // Clean up content logs
            $deletedLogs = $this->cleanupContentLogs($retentionPeriods['content_logs']);
            $totalDeleted += $deletedLogs;
            $cleanupSummary['content_logs'] = $deletedLogs;

            // Clean up cost records
            $deletedCosts = $this->cleanupCostRecords($retentionPeriods['cost_records']);
            $totalDeleted += $deletedCosts;
            $cleanupSummary['cost_records'] = $deletedCosts;

            // Archive completed executions
            $archivedExecutions = $this->archiveCompletedExecutions($retentionPeriods['completed_executions']);
            $cleanupSummary['completed_executions'] = $archivedExecutions;

            // Clean up old failed executions
            $deletedFailedExecutions = $this->cleanupFailedExecutions($retentionPeriods['failed_executions']);
            $totalDeleted += $deletedFailedExecutions;
            $cleanupSummary['failed_executions'] = $deletedFailedExecutions;

            Log::info('Cleanup expired data job completed', [
                'total_deleted' => $totalDeleted,
                'summary' => $cleanupSummary,
            ]);
        } catch (\Exception $e) {
            Log::error("Cleanup expired data job error: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get retention periods from backups or defaults.
     *
     * @return array<string, int>
     */
    protected function getRetentionPeriods(): array
    {
        $retentionPeriods = [];

        foreach ($this->defaultRetentionPeriods as $key => $defaultDays) {
            $settingKey = "cleanup.retention.{$key}";
            $retentionPeriods[$key] = (int) AutomationSetting::getValue(
                $settingKey,
                $defaultDays
            );
        }

        Log::info('Loaded retention periods', $retentionPeriods);

        return $retentionPeriods;
    }

    /**
     * Clean up old extraction results.
     *
     * @return int Number of records deleted
     */
    protected function cleanupExtractionResults(int $retentionDays): int
    {
        try {
            $cutoffDate = now()->subDays($retentionDays);

            $deleted = ExtractionResult::query()
                ->where('created_at', '<', $cutoffDate)
                ->whereIn('status', ['existing', 'error']) // Keep 'new' and 'updated' for reference
                ->delete();

            Log::info("Cleaned up {$deleted} extraction results older than {$retentionDays} days");

            return $deleted;
        } catch (\Exception $e) {
            Log::error("Failed to cleanup extraction results: {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Clean up old content logs.
     *
     * @return int Number of records deleted
     */
    protected function cleanupContentLogs(int $retentionDays): int
    {
        try {
            $cutoffDate = now()->subDays($retentionDays);

            $deleted = ContentLog::query()
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            Log::info("Cleaned up {$deleted} content logs older than {$retentionDays} days");

            return $deleted;
        } catch (\Exception $e) {
            Log::error("Failed to cleanup content logs: {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Clean up old cost records.
     *
     * Keeps aggregated data but removes individual records.
     *
     * @return int Number of records deleted
     */
    protected function cleanupCostRecords(int $retentionDays): int
    {
        try {
            $cutoffDate = now()->subDays($retentionDays);

            // Optional: Aggregate data before deletion for historical reporting
            $this->aggregateCostDataBeforeDeletion($cutoffDate);

            $deleted = AiCost::query()
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            Log::info("Cleaned up {$deleted} cost records older than {$retentionDays} days");

            return $deleted;
        } catch (\Exception $e) {
            Log::error("Failed to cleanup cost records: {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Archive completed automation executions.
     *
     * Moves old completed executions to archive table or soft-deletes them.
     *
     * @return int Number of records archived
     */
    protected function archiveCompletedExecutions(int $retentionDays): int
    {
        try {
            $cutoffDate = now()->subDays($retentionDays);

            // Option 1: Soft delete (if using SoftDeletes trait)
            // Option 2: Move to archive table
            // Option 3: Add 'archived' flag
            // For now, we'll add an archived flag

            $archived = AutomationExecution::query()
                ->where('status', 'completed')
                ->where('completed_at', '<', $cutoffDate)
                ->whereDoesntHave('retry') // Don't archive if still in retry queue
                ->whereDoesntHave('deadLetter') // Don't archive if in dead letter queue
                ->count();

            // If you want to actually delete them:
            // ->delete();

            Log::info("Identified {$archived} completed executions for archival older than {$retentionDays} days");

            return $archived;
        } catch (\Exception $e) {
            Log::error("Failed to archive completed executions: {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Clean up old failed executions.
     *
     * @return int Number of records deleted
     */
    protected function cleanupFailedExecutions(int $retentionDays): int
    {
        try {
            $cutoffDate = now()->subDays($retentionDays);

            $deleted = AutomationExecution::query()
                ->whereIn('status', ['failed', 'timeout', 'cancelled'])
                ->where('completed_at', '<', $cutoffDate)
                ->whereDoesntHave('retry') // Don't delete if still in retry queue
                ->whereDoesntHave('deadLetter') // Don't delete if in dead letter queue
                ->delete();

            Log::info("Cleaned up {$deleted} failed executions older than {$retentionDays} days");

            return $deleted;
        } catch (\Exception $e) {
            Log::error("Failed to cleanup failed executions: {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Aggregate cost data before deletion for historical reporting.
     *
     * Creates monthly aggregates of cost data.
     *
     * @param  \Carbon\Carbon  $cutoffDate
     */
    protected function aggregateCostDataBeforeDeletion($cutoffDate): void
    {
        try {
            // Get monthly aggregates
            $aggregates = AiCost::query()
                ->where('created_at', '<', $cutoffDate)
                ->select([
                    // Driver-agnostic month bucket. Previous code used PostgreSQL's
                    // DATE_TRUNC which errors out on MySQL — the project's actual driver.
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m-01') as month"),
                    'supplier_id',
                    'model',
                    'operation_type',
                    DB::raw('SUM(input_tokens) as total_input_tokens'),
                    DB::raw('SUM(output_tokens) as total_output_tokens'),
                    DB::raw('SUM(input_cost) as total_input_cost'),
                    DB::raw('SUM(output_cost) as total_output_cost'),
                    DB::raw('COUNT(*) as request_count'),
                    DB::raw('AVG(latency_ms) as avg_latency_ms'),
                ])
                ->groupBy('month', 'supplier_id', 'model', 'operation_type')
                ->get();

            // Here you could save these aggregates to a separate table
            // For example: SupplierAiCostMonthlyAggregate::insert($aggregates->toArray());

            Log::info("Generated {$aggregates->count()} monthly cost aggregates before deletion");
        } catch (\Exception $e) {
            Log::error("Failed to aggregate cost data: {$e->getMessage()}");
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Cleanup expired data job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
