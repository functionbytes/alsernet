<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Modules\Document\Entities\Document;

class InitializeDocumentWorkflows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:initialize-workflows
                            {--status=pending : Filter documents by validation status (pending, in_validation, all)}
                            {--limit=0 : Limit number of documents to process (0 = no limit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize validation workflows for existing documents that are missing them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting document workflow initialization...');

        $status = $this->option('status');
        $limit = (int) $this->option('limit');

        // Build query for documents needing workflow initialization
        $query = Document::whereNull('current_validator_group');

        if ($status === 'all') {
            $query = Document::whereNull('current_validator_group');
        } else {
            $query = $query->where('validation_status', $status);
        }

        if ($limit > 0) {
            $query = $query->limit($limit);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('No documents found needing workflow initialization.');

            return 0;
        }

        $this->info("Found {$total} documents to initialize.");

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $initialized = 0;
        $failed = 0;

        $documents = $query->get();

        foreach ($documents as $document) {
            try {
                $stages = $document->getValidationWorkflowStages();

                if (empty($stages)) {
                    $progressBar->advance();
                    $failed++;

                    continue;
                }

                $document->initializeValidationWorkflow($stages);
                $initialized++;
            } catch (\Exception $e) {
                $this->error("\nError initializing document {$document->uid}: {$e->getMessage()}");
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info('✓ Workflow initialization completed!');
        $this->line("  Initialized: <fg=green>{$initialized}</fg=green> documents");
        $this->line("  Failed/Skipped: <fg=yellow>{$failed}</fg=yellow> documents");
        $this->line("  Total processed: <fg=blue>{$total}</fg=blue> documents");

        return 0;
    }
}
