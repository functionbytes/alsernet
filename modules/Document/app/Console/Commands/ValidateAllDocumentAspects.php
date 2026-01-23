<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentValidatorGroup;

class ValidateAllDocumentAspects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:validate-all
                            {--fix : Automatically fix documents with issues}
                            {--type= : Filter by document type slug}
                            {--status= : Filter by validation status}
                            {--limit=0 : Limit number of documents (0 = all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate all document aspects: stages, workflow, validators, status, and consistency';

    protected array $issues = [];

    protected int $totalIssuesFound = 0;

    protected int $totalIssuesFixed = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║   COMPLETE DOCUMENT VALIDATION AND REPAIR SYSTEM           ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $fix = $this->option('fix');
        if ($fix) {
            $this->info('✓ AUTO-FIX MODE ENABLED - Issues will be fixed automatically');
        } else {
            $this->info('⚠ DRY-RUN MODE - No changes will be made. Use --fix to apply fixes');
        }

        // Build query
        $query = Document::query();

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
            $this->warn('No documents found matching criteria.');

            return 0;
        }

        $this->info("\nValidating {$total} documents...");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $statistics = [
            'total' => 0,
            'healthy' => 0,
            'issues_found' => 0,
            'issues_fixed' => 0,
            'validation_errors' => [],
        ];

        $documents = $query->get();

        foreach ($documents as $document) {
            $statistics['total']++;
            $documentIssues = [];

            try {
                // Validar múltiples aspectos
                $this->validateStages($document, $documentIssues);
                $this->validateWorkflowConsistency($document, $documentIssues);
                $this->validateValidatorGroup($document, $documentIssues);
                $this->validateStatusTransitions($document, $documentIssues);
                $this->validateRequiredFields($document, $documentIssues);
                $this->validateWorkflowHistory($document, $documentIssues);

                if (empty($documentIssues)) {
                    $statistics['healthy']++;
                } else {
                    $statistics['issues_found']++;
                    $this->issues[$document->uid] = [
                        'id' => $document->id,
                        'type' => $document->documentType?->slug ?? 'unknown',
                        'issues' => $documentIssues,
                    ];

                    // Aplicar fixes si está habilitado
                    if ($fix) {
                        $fixed = $this->fixDocumentIssues($document, $documentIssues);
                        if ($fixed > 0) {
                            $statistics['issues_fixed'] += $fixed;
                        }
                    }
                }
            } catch (\Exception $e) {
                $statistics['validation_errors'][] = [
                    'uid' => $document->uid,
                    'error' => $e->getMessage(),
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        // Display results
        $this->displayResults($statistics, $fix);

        return 0;
    }

    /**
     * Validate stage configuration
     */
    protected function validateStages(Document $document, array &$issues): void
    {
        try {
            $correctStages = $document->getValidationWorkflowStages();
            $correctCount = count($correctStages);

            if ($document->total_stages !== $correctCount) {
                $issues[] = [
                    'type' => 'stage_count',
                    'message' => "Stage count mismatch: {$document->total_stages} (current) vs {$correctCount} (expected)",
                    'current' => $document->total_stages,
                    'expected' => $correctCount,
                ];
            }

            // Validar que current_stage no exceda total_stages
            if ($document->total_stages > 0 && $document->current_stage > $document->total_stages) {
                $issues[] = [
                    'type' => 'invalid_current_stage',
                    'message' => "Current stage ({$document->current_stage}) exceeds total stages ({$document->total_stages})",
                    'current' => $document->current_stage,
                    'total' => $document->total_stages,
                ];
            }

            // Validar que current_stage sea >= 1 si hay workflow
            if ($document->total_stages > 0 && $document->current_stage < 1) {
                $issues[] = [
                    'type' => 'invalid_stage_number',
                    'message' => "Current stage must be >= 1, found: {$document->current_stage}",
                ];
            }
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'stage_validation_error',
                'message' => "Error validating stages: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Validate workflow consistency
     */
    protected function validateWorkflowConsistency(Document $document, array &$issues): void
    {
        $hasWorkflow = $document->total_stages > 0;

        // Si tiene workflow, debe tener status de validación
        if ($hasWorkflow) {
            if (! in_array($document->validation_status, ['pending', 'in_validation', 'approved', 'rejected'])) {
                $issues[] = [
                    'type' => 'invalid_validation_status',
                    'message' => "Invalid validation status: {$document->validation_status}",
                ];
            }

            // Si está en validación, debe tener validator group
            if (in_array($document->validation_status, ['pending', 'in_validation'])) {
                if (empty($document->current_validator_group)) {
                    $issues[] = [
                        'type' => 'missing_validator_group',
                        'message' => "Document in validation but missing validator group assignment",
                    ];
                }
            }

            // Si está completado, validation_completed_at debe estar presente
            if (in_array($document->validation_status, ['approved', 'rejected'])) {
                if (! $document->validation_completed_at) {
                    $issues[] = [
                        'type' => 'missing_completion_timestamp',
                        'message' => "Document status is {$document->validation_status} but validation_completed_at is empty",
                    ];
                }
            }

            // Si está en validación, validation_started_at debe estar presente
            if ($document->validation_status === 'in_validation' && ! $document->validation_started_at) {
                $issues[] = [
                    'type' => 'missing_start_timestamp',
                    'message' => "Document status is in_validation but validation_started_at is empty",
                ];
            }
        } else {
            // Sin workflow, debe estar en pending
            if ($document->validation_status !== 'pending') {
                $issues[] = [
                    'type' => 'invalid_workflow_status',
                    'message' => "Document without workflow has status: {$document->validation_status} (should be pending)",
                ];
            }
        }
    }

    /**
     * Validate validator group exists
     */
    protected function validateValidatorGroup(Document $document, array &$issues): void
    {
        if (! empty($document->current_validator_group)) {
            $group = DocumentValidatorGroup::findByKey($document->current_validator_group);

            if (! $group) {
                $issues[] = [
                    'type' => 'invalid_validator_group',
                    'message' => "Validator group '{$document->current_validator_group}' does not exist",
                ];
            } elseif (! $group->is_active) {
                $issues[] = [
                    'type' => 'inactive_validator_group',
                    'message' => "Validator group '{$document->current_validator_group}' is inactive",
                ];
            }
        }
    }

    /**
     * Validate status transitions
     */
    protected function validateStatusTransitions(Document $document, array &$issues): void
    {
        // Si está aprobado, current_stage debe ser igual a total_stages
        if ($document->validation_status === 'approved') {
            if ($document->total_stages > 0 && $document->current_stage !== $document->total_stages) {
                $issues[] = [
                    'type' => 'invalid_approved_stage',
                    'message' => "Approved document has stage {$document->current_stage}, expected {$document->total_stages}",
                ];
            }

            // Si está aprobado, current_validator_group debe ser null
            if (! empty($document->current_validator_group)) {
                $issues[] = [
                    'type' => 'invalid_approved_group',
                    'message' => "Approved document has validator group '{$document->current_validator_group}' (should be null)",
                ];
            }
        }
    }

    /**
     * Validate required fields
     */
    protected function validateRequiredFields(Document $document, array &$issues): void
    {
        // Validar que tenga document_type
        if (empty($document->type_id) || ! $document->documentType) {
            $issues[] = [
                'type' => 'missing_document_type',
                'message' => "Document has no associated document type",
            ];
        }

        // Validar que tenga UID
        if (empty($document->uid)) {
            $issues[] = [
                'type' => 'missing_uid',
                'message' => "Document has no UID",
            ];
        }
    }

    /**
     * Validate workflow history integrity
     */
    protected function validateWorkflowHistory(Document $document, array &$issues): void
    {
        try {
            $history = $document->validationHistory()->orderBy('validated_at', 'asc')->get();

            if ($history->isNotEmpty()) {
                // Verificar que los stage numbers en history sean válidos
                foreach ($history as $record) {
                    if ($record->stage_number < 1 || $record->stage_number > $document->total_stages) {
                        $issues[] = [
                            'type' => 'invalid_history_stage',
                            'message' => "History record has invalid stage {$record->stage_number} (total stages: {$document->total_stages})",
                        ];
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            // Si falla, simplemente ignorar - no es crítico
        }
    }

    /**
     * Fix document issues
     */
    protected function fixDocumentIssues(Document $document, array $issues): int
    {
        $fixed = 0;

        foreach ($issues as $issue) {
            try {
                switch ($issue['type']) {
                    case 'stage_count':
                        $correctStages = $document->getValidationWorkflowStages();
                        $document->total_stages = count($correctStages);
                        $fixed++;
                        break;

                    case 'invalid_current_stage':
                        if ($document->total_stages > 0) {
                            $document->current_stage = min($document->current_stage, $document->total_stages);
                            if ($document->validation_status !== 'approved') {
                                $document->validation_status = 'pending';
                            }
                            $fixed++;
                        }
                        break;

                    case 'invalid_stage_number':
                        if ($document->total_stages > 0) {
                            $document->current_stage = max(1, $document->current_stage);
                            $fixed++;
                        }
                        break;

                    case 'missing_validator_group':
                        if ($document->total_stages > 0 && $document->current_stage > 0) {
                            $stages = $document->getValidationWorkflowStages();
                            $stageName = $stages[$document->current_stage - 1] ?? null;
                            if ($stageName) {
                                $document->current_validator_group = $stageName;
                                $fixed++;
                            }
                        }
                        break;

                    case 'missing_start_timestamp':
                        $document->validation_started_at = $document->updated_at ?? now();
                        $fixed++;
                        break;

                    case 'missing_completion_timestamp':
                        $document->validation_completed_at = $document->updated_at ?? now();
                        $fixed++;
                        break;

                    case 'invalid_approved_stage':
                        if ($document->total_stages > 0) {
                            $document->current_stage = $document->total_stages;
                            $fixed++;
                        }
                        break;

                    case 'invalid_approved_group':
                        $document->current_validator_group = null;
                        $fixed++;
                        break;
                }
            } catch (\Exception $e) {
                // Si falla la corrección, continuar con el siguiente
            }
        }

        if ($fixed > 0) {
            try {
                $document->save();
                $this->totalIssuesFixed += $fixed;
            } catch (\Exception $e) {
                // Si falla el save, descontar los fixes
                $this->totalIssuesFixed -= $fixed;
            }
        }

        return $fixed;
    }

    /**
     * Display validation results
     */
    protected function displayResults(array $statistics, bool $fix): void
    {
        $this->newLine(2);
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║             VALIDATION RESULTS SUMMARY                     ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Summary stats
        $this->line("  Total documents processed: <fg=blue>{$statistics['total']}</fg=blue>");
        $this->line("  ✓ Healthy documents: <fg=green>{$statistics['healthy']}</fg=green>");
        $this->line("  ✗ Documents with issues: <fg=yellow>{$statistics['issues_found']}</fg=yellow>");

        if ($fix && $statistics['issues_fixed'] > 0) {
            $this->line("  ✓ Issues fixed: <fg=green>{$statistics['issues_fixed']}</fg=green>");
        }

        if (! empty($statistics['validation_errors'])) {
            $this->line("  ⚠ Validation errors: <fg=red>" . count($statistics['validation_errors']) . '</fg=red>');
        }

        // Detailed issues
        if (! empty($this->issues)) {
            $this->newLine();
            $this->info('─────────────────────────────────────────────────────────');
            $this->info('DETAILED ISSUES FOUND');
            $this->info('─────────────────────────────────────────────────────────');
            $this->newLine();

            $issueCount = 0;
            foreach ($this->issues as $uid => $documentData) {
                if ($issueCount >= 50) {
                    $remaining = count($this->issues) - 50;
                    $this->line("  ... and <fg=yellow>{$remaining}</fg=yellow> more documents with issues");
                    break;
                }

                $this->line("  <fg=cyan>{$uid}</fg=cyan> (Type: <fg=blue>{$documentData['type']}</fg=blue>)");

                foreach ($documentData['issues'] as $issue) {
                    $this->line("    • <fg=yellow>{$issue['type']}</fg=yellow>: {$issue['message']}");
                }

                $this->newLine();
                $issueCount++;
            }
        }

        // Final summary
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════╗');

        if ($fix) {
            $healthyPercent = $statistics['total'] > 0
                ? round((($statistics['healthy'] + $statistics['issues_fixed']) / $statistics['total']) * 100, 2)
                : 0;

            $this->info("✓ Validation and repair completed!");
            $this->info("  System health: <fg=green>{$healthyPercent}%</fg=green>");
        } else {
            $healthyPercent = $statistics['total'] > 0
                ? round(($statistics['healthy'] / $statistics['total']) * 100, 2)
                : 0;

            $this->info("ℹ Validation completed (dry-run mode)");
            $this->info("  System health: <fg=yellow>{$healthyPercent}%</fg=yellow>");

            if ($statistics['issues_found'] > 0) {
                $this->newLine();
                $this->info("  Run with --fix flag to repair <fg=yellow>{$statistics['issues_found']}</fg=yellow> documents");
            }
        }

        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }
}
