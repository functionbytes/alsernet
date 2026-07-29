<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Modules\Document\Entities\Document;

class ReinitializeDocumentWorkflows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:reinitialize-workflows
                            {--type= : Filter by document type slug}
                            {--status= : Filter by validation status (pending, in_validation, approved, rejected, all)}
                            {--limit=0 : Limit number of documents (0 = no limit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reinitialize validation workflows for all documents based on current type and conditions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║      REINITIALIZE DOCUMENT VALIDATION WORKFLOWS             ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Build query
        $query = Document::query();

        if ($type = $this->option('type')) {
            $query->whereHas('documentType', function ($q) use ($type) {
                $q->where('slug', $type);
            });
            $this->info("Filtering by type: <fg=cyan>$type</fg=cyan>");
        }

        if ($status = $this->option('status')) {
            if ($status !== 'all') {
                $query->where('validation_status', $status);
                $this->info("Filtering by status: <fg=cyan>$status</fg=cyan>");
            }
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
            $this->info("Limiting to: <fg=cyan>$limit</fg=cyan> documents");
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('No documents found matching the criteria.');
            return 0;
        }

        $this->newLine();
        $this->info("Found <fg=blue>$total</fg=blue> documents to reinitialize");
        $this->newLine();

        // Confirm action
        if (!$this->confirm('This will reinitialize workflows for all matching documents. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->newLine();

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $statistics = [
            'total' => 0,
            'reinitialized' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $documents = $query->get();

        foreach ($documents as $document) {
            $statistics['total']++;

            try {
                // Get the correct stages for this document
                $stages = $document->getValidationWorkflowStages();

                if (empty($stages)) {
                    $statistics['failed']++;
                    $statistics['errors'][] = [
                        'uid' => $document->uid,
                        'error' => 'No stages found for this document type',
                    ];
                    $progressBar->advance();
                    continue;
                }

                // Reinitialize the workflow
                $result = $document->initializeValidationWorkflow($stages);

                if ($result) {
                    $statistics['reinitialized']++;
                } else {
                    $statistics['failed']++;
                    $statistics['errors'][] = [
                        'uid' => $document->uid,
                        'error' => 'Failed to reinitialize workflow',
                    ];
                }
            } catch (\Exception $e) {
                $statistics['failed']++;
                $statistics['errors'][] = [
                    'uid' => $document->uid,
                    'error' => $e->getMessage(),
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        // Display results
        $this->displayResults($statistics);

        return 0;
    }

    /**
     * Display results
     */
    protected function displayResults(array $statistics): void
    {
        $this->newLine(2);
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║           REINITIALIZATION RESULTS SUMMARY                 ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line("  Total processed: <fg=blue>{$statistics['total']}</fg=blue> documents");
        $this->line("  ✓ Reinitialized: <fg=green>{$statistics['reinitialized']}</fg=green> documents");

        if ($statistics['failed'] > 0) {
            $this->line("  ✗ Failed: <fg=red>{$statistics['failed']}</fg=red> documents");
        }

        // Show errors if any
        if (!empty($statistics['errors'])) {
            $this->newLine();
            $this->info('─────────────────────────────────────────────────────────');
            $this->info('ERRORS');
            $this->info('─────────────────────────────────────────────────────────');
            $this->newLine();

            foreach ($statistics['errors'] as $error) {
                $this->line("  <fg=red>✗</fg=red> <fg=cyan>{$error['uid']}</fg=cyan>");
                $this->line("    Error: {$error['error']}");
            }
        }

        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════╗');

        if ($statistics['reinitialized'] > 0) {
            $this->info("✓ Successfully reinitialized {$statistics['reinitialized']} documents!");
        }

        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }
}
