<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Modules\Document\Entities\Document;

class ValidateDocumentStages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:validate-stages
                            {--fix : Automatically fix documents with incorrect stage counts}
                            {--document-id= : Validate specific document by ID}
                            {--document-uid= : Validate specific document by UID}
                            {--type= : Filter by document type slug (dni, corta, rifle, etc.)}
                            {--status= : Filter by validation status (pending, in_validation, approved, rejected)}
                            {--limit=0 : Limit number of documents to process (0 = no limit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate and fix document validation stage counts based on document type and conditions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting document stage validation...');

        // Build query
        $query = Document::query();

        // Apply filters
        if ($documentId = $this->option('document-id')) {
            $query->where('id', $documentId);
        }

        if ($documentUid = $this->option('document-uid')) {
            $query->where('uid', $documentUid);
        }

        if ($type = $this->option('type')) {
            $query->whereHas('documentType', function ($q) use ($type) {
                $q->where('slug', $type);
            });
        }

        if ($status = $this->option('status')) {
            $query->where('validation_status', $status);
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('No documents found matching the criteria.');

            return 0;
        }

        $this->info("Found {$total} documents to validate.");

        $fix = $this->option('fix');
        if ($fix) {
            $this->info('✓ AUTO-FIX MODE ENABLED - Documents will be updated automatically.');
        } else {
            $this->info('⚠ DRY-RUN MODE - No changes will be made. Use --fix to apply changes.');
        }

        $this->newLine();

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $statistics = [
            'total_processed' => 0,
            'correct' => 0,
            'incorrect' => 0,
            'fixed' => 0,
            'error' => 0,
            'details' => [],
        ];

        $documents = $query->get();

        foreach ($documents as $document) {
            try {
                $correctStages = $document->getValidationWorkflowStages();
                $correctStageCount = count($correctStages);
                $currentStageCount = $document->total_stages;

                $statistics['total_processed']++;

                if ($correctStageCount === $currentStageCount) {
                    // Document is correct
                    $statistics['correct']++;
                } else {
                    // Document needs correction
                    $statistics['incorrect']++;

                    $detail = [
                        'document_id' => $document->id,
                        'uid' => $document->uid,
                        'type' => $document->documentType?->slug ?? 'unknown',
                        'current_stages' => $currentStageCount,
                        'correct_stages' => $correctStageCount,
                        'current_stage' => $document->current_stage,
                        'validation_status' => $document->validation_status,
                        'stages' => $correctStages,
                    ];

                    if ($fix) {
                        // Update the document
                        try {
                            $document->total_stages = $correctStageCount;

                            // If the document has stages but is at an invalid stage, adjust
                            if ($correctStageCount > 0 && $document->current_stage > $correctStageCount) {
                                // Current stage exceeds new total, move to last valid stage
                                $document->current_stage = $correctStageCount;
                                $document->current_validator_group = $correctStages[$correctStageCount - 1] ?? null;

                                // If validation is complete, keep it. Otherwise, mark as pending
                                if ($document->validation_status !== Document::VALIDATION_STATUS_APPROVED) {
                                    $document->validation_status = Document::VALIDATION_STATUS_PENDING;
                                }

                                $detail['action'] = 'adjusted_stage';
                                $detail['new_stage'] = $document->current_stage;
                            } else {
                                $detail['action'] = 'stage_count_updated';
                            }

                            $document->save();
                            $statistics['fixed']++;
                            $detail['status'] = 'fixed';
                        } catch (\Exception $e) {
                            $statistics['error']++;
                            $detail['status'] = 'error';
                            $detail['error'] = $e->getMessage();
                        }
                    } else {
                        $detail['status'] = 'pending_fix';
                    }

                    $statistics['details'][] = $detail;
                }
            } catch (\Exception $e) {
                $statistics['total_processed']++;
                $statistics['error']++;
                $this->error("\nError processing document {$document->uid}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        // Display summary
        $this->newLine(2);
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('STAGE VALIDATION SUMMARY');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $this->line("  Total processed: <fg=blue>{$statistics['total_processed']}</fg=blue> documents");
        $this->line("  ✓ Correct stages: <fg=green>{$statistics['correct']}</fg=green> documents");
        $this->line("  ✗ Incorrect stages: <fg=yellow>{$statistics['incorrect']}</fg=yellow> documents");

        if ($fix) {
            $this->line("  ✓ Fixed: <fg=green>{$statistics['fixed']}</fg=green> documents");
        }

        if ($statistics['error'] > 0) {
            $this->line("  ⚠ Errors: <fg=red>{$statistics['error']}</fg=red> documents");
        }

        // Show detailed incorrect documents
        if (! empty($statistics['details'])) {
            $this->newLine();
            $this->info('───────────────────────────────────────────────────────');
            $this->info('DETAILED RESULTS');
            $this->info('───────────────────────────────────────────────────────');
            $this->newLine();

            foreach ($statistics['details'] as $detail) {
                $status = $detail['status'] ?? 'unknown';
                $statusColor = match ($status) {
                    'fixed' => 'green',
                    'error' => 'red',
                    'pending_fix' => 'yellow',
                    default => 'white',
                };

                $statusIcon = match ($status) {
                    'fixed' => '✓',
                    'error' => '✗',
                    'pending_fix' => '⚠',
                    default => '◆',
                };

                $this->line(
                    "  {$statusIcon} <fg={$statusColor}>{$status}</fg={$statusColor}> " .
                    "UID: <fg=cyan>{$detail['uid']}</fg=cyan> | " .
                    "Type: <fg=blue>{$detail['type']}</fg=blue> | " .
                    "Stages: {$detail['current_stages']} → {$detail['correct_stages']}"
                );

                if (isset($detail['error'])) {
                    $this->line("    Error: <fg=red>{$detail['error']}</fg=red>");
                }

                if (isset($detail['new_stage'])) {
                    $this->line("    Adjusted stage from {$detail['current_stage']} to {$detail['new_stage']}");
                }

                if (! empty($detail['stages'])) {
                    $this->line("    Correct stages: " . implode(' → ', $detail['stages']));
                }
            }
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');

        if ($fix && $statistics['fixed'] > 0) {
            $this->info("✓ Successfully fixed {$statistics['fixed']} documents!");
        } elseif (! $fix && $statistics['incorrect'] > 0) {
            $this->info("ℹ Found {$statistics['incorrect']} documents with incorrect stage counts.");
            $this->info('Run with --fix flag to apply corrections automatically.');
        } else {
            $this->info('✓ All documents have correct stage counts!');
        }

        return 0;
    }
}
