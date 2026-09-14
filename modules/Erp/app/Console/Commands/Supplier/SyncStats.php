<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;
use Modules\Supplier\Entities\SupplierSyncAudit;

class SyncStats extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:sync-stats
                            {--hours=24 : Number of hours to analyze}
                            {--entity= : Filter by entity type (sports, categories, products, etc.)}
                            {--direction= : Filter by sync direction (erp_to_supplier, supplier_to_erp)}
                            {--failures : Show only syncs with failures}
                            {--slow=10 : Show syncs slower than N seconds}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Show synchronization statistics from audit trail';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $entity = $this->option('entity');
        $direction = $this->option('direction');
        $failuresOnly = $this->option('failures');
        $slowThreshold = (float) $this->option('slow');

        $this->info('📊 Sync Statistics - Last '.$hours.' hours');
        $this->newLine();

        // Build query
        $query = SupplierSyncAudit::recent($hours);

        if ($entity) {
            $query->byEntityType($entity);
        }

        if ($direction) {
            $query->byDirection($direction);
        }

        if ($failuresOnly) {
            $query->withFailures();
        }

        if ($this->option('slow') !== '10') {
            $query->slow($slowThreshold);
        }

        $audits = $query->orderByDesc('synced_at')->get();

        if ($audits->isEmpty()) {
            $this->warn('No synchronization records found for the specified criteria');

            return self::SUCCESS;
        }

        // Overall summary
        $this->displayOverallSummary($audits, $hours);
        $this->newLine();

        // Per-entity breakdown
        $this->displayEntityBreakdown($audits);
        $this->newLine();

        // Recent syncs
        $this->displayRecentSyncs($audits);
        $this->newLine();

        // Issues detection
        $this->displayIssues($audits);

        return self::SUCCESS;
    }

    /**
     * Display overall summary
     */
    protected function displayOverallSummary($audits, int $hours): void
    {
        $totalSyncs = $audits->count();
        $totalRecordsSynced = $audits->sum('records_synced');
        $totalRecordsFailed = $audits->sum('records_failed');
        $totalProcessed = $totalRecordsSynced + $totalRecordsFailed;
        $avgTime = $audits->avg('elapsed_seconds');
        $avgMemory = $audits->avg('memory_mb');

        $successRate = $totalProcessed > 0
            ? round(($totalRecordsSynced / $totalProcessed) * 100, 2)
            : 100;

        $this->info('📈 Overall Summary');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Sync Operations', number_format($totalSyncs)],
                ['Records Synced ✅', '<fg=green>'.number_format($totalRecordsSynced).'</>'],
                ['Records Failed ❌', $totalRecordsFailed > 0 ? '<fg=red>'.number_format($totalRecordsFailed).'</>' : '<fg=green>0</>'],
                ['Total Processed', number_format($totalProcessed)],
                ['Success Rate', $successRate >= 95 ? "<fg=green>{$successRate}%</>" : "<fg=yellow>{$successRate}%</>"],
                ['Avg Time per Sync', round($avgTime, 2).'s'],
                ['Avg Memory Usage', round($avgMemory, 2).' MB'],
                ['Time Period', "Last {$hours} hours"],
            ]
        );
    }

    /**
     * Display per-entity breakdown
     */
    protected function displayEntityBreakdown($audits): void
    {
        $this->info('📋 Breakdown by Entity Type');

        $breakdown = $audits->groupBy('entity_type')->map(function ($group) {
            return [
                'syncs' => $group->count(),
                'records' => $group->sum('records_synced'),
                'failures' => $group->sum('records_failed'),
                'avg_time' => round($group->avg('elapsed_seconds'), 2),
            ];
        });

        $rows = [];
        foreach ($breakdown as $entityType => $stats) {
            $entityName = SupplierSyncAudit::make(['entity_type' => $entityType])->entity_type_name;

            $rows[] = [
                $entityName,
                number_format($stats['syncs']),
                number_format($stats['records']),
                $stats['failures'] > 0 ? '<fg=red>'.number_format($stats['failures']).'</>' : '<fg=green>0</>',
                $stats['avg_time'].'s',
            ];
        }

        // Sort by most syncs
        usort($rows, fn ($a, $b) => (int) str_replace(',', '', $b[1]) <=> (int) str_replace(',', '', $a[1]));

        $this->table(
            ['Entity', 'Syncs', 'Records', 'Failures', 'Avg Time'],
            $rows
        );
    }

    /**
     * Display recent syncs
     */
    protected function displayRecentSyncs($audits): void
    {
        $this->info('⏱️  Recent Syncs (Last 10)');

        $recent = $audits->take(10);

        $rows = [];
        foreach ($recent as $audit) {
            $status = $audit->hasFailures()
                ? '<fg=red>❌ Failed ('.$audit->records_failed.')</>  '
                : '<fg=green>✅ Success</>';

            $rows[] = [
                $audit->synced_at->format('Y-m-d H:i:s'),
                $audit->entity_type_name,
                $audit->sync_direction_name,
                number_format($audit->records_synced),
                round($audit->elapsed_seconds, 2).'s',
                $status,
            ];
        }

        $this->table(
            ['Time', 'Entity', 'Direction', 'Records', 'Duration', 'Status'],
            $rows
        );
    }

    /**
     * Display issues and warnings
     */
    protected function displayIssues($audits): void
    {
        $issues = [];

        // Check for failures
        $withFailures = $audits->filter->hasFailures();
        if ($withFailures->isNotEmpty()) {
            $issues[] = [
                '⚠️  Failures Detected',
                $withFailures->count().' syncs had failures',
                '<fg=red>Review logs and Dead Letter Queue</>',
            ];
        }

        // Check for slow syncs
        $slowSyncs = $audits->filter->isSlow(10);
        if ($slowSyncs->isNotEmpty()) {
            $issues[] = [
                '🐌 Slow Syncs',
                $slowSyncs->count().' syncs took >10 seconds',
                '<fg=yellow>Consider optimizing queries or increasing chunk size</>',
            ];
        }

        // Check for high memory usage
        $highMemory = $audits->filter(fn ($a) => $a->memory_mb > 100);
        if ($highMemory->isNotEmpty()) {
            $issues[] = [
                '💾 High Memory Usage',
                $highMemory->count().' syncs used >100 MB',
                '<fg=yellow>Consider reducing chunk size or batch size</>',
            ];
        }

        // Check sync frequency
        $syncsByEntity = $audits->groupBy('entity_type');
        foreach ($syncsByEntity as $entity => $group) {
            if ($group->count() > 100) {
                $entityName = SupplierSyncAudit::make(['entity_type' => $entity])->entity_type_name;
                $issues[] = [
                    '🔥 High Sync Frequency',
                    "{$entityName}: {$group->count()} syncs in period",
                    '<fg=yellow>Entity may be changing too frequently</>',
                ];
            }
        }

        if (empty($issues)) {
            $this->info('✅ No issues detected - all syncs are healthy!');

            return;
        }

        $this->warn('⚠️  Issues & Recommendations');
        $this->newLine();

        $this->table(
            ['Issue', 'Details', 'Recommendation'],
            $issues
        );
    }
}
