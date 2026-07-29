<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Modules\Document\Entities\Document;

class ValidateDocumentMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:validate-media
                            {--fix : Automatically fix documents with media inconsistencies}
                            {--type= : Filter by document type slug}
                            {--status= : Filter by validation status}
                            {--limit=0 : Limit number of documents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate document media files, consistency, and denormalized data';

    protected array $issues = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║     DOCUMENT MEDIA & DATA CONSISTENCY VALIDATION           ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $fix = $this->option('fix');
        if ($fix) {
            $this->info('✓ AUTO-FIX MODE ENABLED');
        } else {
            $this->info('⚠ DRY-RUN MODE - Use --fix to apply repairs');
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
            $this->warn('No documents found.');

            return 0;
        }

        $this->info("\nValidating media for {$total} documents...\n");

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $statistics = [
            'total' => 0,
            'healthy' => 0,
            'issues_found' => 0,
            'issues_fixed' => 0,
            'by_type' => [],
        ];

        $documents = $query->get();

        foreach ($documents as $document) {
            $statistics['total']++;
            $documentIssues = [];

            try {
                $this->validateDocumentMedia($document, $documentIssues);
                $this->validateUploadedDocumentsJson($document, $documentIssues);
                $this->validateAdditionalAttachments($document, $documentIssues);
                $this->validateRequiredDocuments($document, $documentIssues);

                if (empty($documentIssues)) {
                    $statistics['healthy']++;
                } else {
                    $statistics['issues_found']++;
                    $docType = $document->documentType?->slug ?? 'unknown';
                    $statistics['by_type'][$docType] = ($statistics['by_type'][$docType] ?? 0) + 1;

                    $this->issues[$document->uid] = [
                        'id' => $document->id,
                        'type' => $docType,
                        'issues' => $documentIssues,
                    ];

                    // Apply fixes
                    if ($fix) {
                        $fixed = $this->fixMediaIssues($document, $documentIssues);
                        if ($fixed > 0) {
                            $statistics['issues_fixed'] += $fixed;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->line("\n<fg=red>Error processing {$document->uid}: {$e->getMessage()}</fg=red>");
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        // Display results
        $this->displayResults($statistics, $fix);

        return 0;
    }

    /**
     * Validate document media files
     */
    protected function validateDocumentMedia(Document $document, array &$issues): void
    {
        try {
            $mediaFiles = $document->getMedia('documents');
            $uploadedTypes = [];

            foreach ($mediaFiles as $media) {
                // Verify media file exists
                $filePath = $media->getPath();
                if (! file_exists($filePath)) {
                    $issues[] = [
                        'type' => 'missing_media_file',
                        'message' => "Media file missing: {$media->file_name}",
                    ];
                }

                // Verify document_type custom property
                $docType = $media->getCustomProperty('document_type');
                if (empty($docType)) {
                    $issues[] = [
                        'type' => 'missing_document_type_property',
                        'message' => "Media {$media->file_name} missing document_type property",
                    ];
                } else {
                    $uploadedTypes[] = $docType;
                }

                // Verify media size is reasonable
                if ($media->size === 0) {
                    $issues[] = [
                        'type' => 'zero_size_file',
                        'message' => "Media {$media->file_name} has zero size",
                    ];
                }
            }
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'media_validation_error',
                'message' => "Error validating media: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Validate uploaded_documents JSON consistency
     */
    protected function validateUploadedDocumentsJson(Document $document, array &$issues): void
    {
        try {
            $actualUploaded = $document->getUploadedDocumentTypes();
            $jsonUploaded = $document->uploaded_documents ?? [];

            // Normalize arrays for comparison
            sort($actualUploaded);
            sort($jsonUploaded);

            if (json_encode($actualUploaded) !== json_encode($jsonUploaded)) {
                $issues[] = [
                    'type' => 'uploaded_documents_mismatch',
                    'message' => "uploaded_documents JSON doesn't match actual media. Actual: " . implode(', ', $actualUploaded),
                ];
            }
        } catch (\Exception $e) {
            // Continue if sync fails
        }
    }

    /**
     * Validate additional attachments
     */
    protected function validateAdditionalAttachments(Document $document, array &$issues): void
    {
        try {
            $attachmentMedia = $document->getMedia('additional_attachments');

            foreach ($attachmentMedia as $media) {
                // Verify attachment file exists
                $filePath = $media->getPath();
                if (! file_exists($filePath)) {
                    $issues[] = [
                        'type' => 'missing_attachment_file',
                        'message' => "Attachment file missing: {$media->file_name}",
                    ];
                }

                // Verify original_name property
                $originalName = $media->getCustomProperty('original_name');
                if (empty($originalName)) {
                    $issues[] = [
                        'type' => 'missing_attachment_original_name',
                        'message' => "Attachment {$media->file_name} missing original_name property",
                    ];
                }
            }

            // Verify additional_attachments JSON
            $actualAttachments = $document->getAdditionalAttachmentsWithDetails();
            $jsonAttachments = $document->additional_attachments ?? [];

            if (count($actualAttachments) !== count($jsonAttachments)) {
                $issues[] = [
                    'type' => 'additional_attachments_count_mismatch',
                    'message' => "additional_attachments JSON count doesn't match actual files",
                ];
            }
        } catch (\Exception $e) {
            // Continue if validation fails
        }
    }

    /**
     * Validate required documents completeness
     */
    protected function validateRequiredDocuments(Document $document, array &$issues): void
    {
        try {
            // Get required documents
            $required = $document->getRequiredDocumentsWithLabels();
            $missing = $document->getMissingDocuments();

            // Verify required_documents JSON matches actual
            $actualRequired = $document->getRequiredDocuments();
            $jsonRequired = $document->required_documents ?? [];

            sort($actualRequired);
            sort($jsonRequired);

            if (json_encode($actualRequired) !== json_encode($jsonRequired)) {
                $issues[] = [
                    'type' => 'required_documents_mismatch',
                    'message' => "required_documents JSON doesn't match actual requirements",
                ];
            }

            // Note: Missing documents are expected for documents still in upload phase
            // Only warn if document is in advanced validation stages
            if ($document->validation_status === 'in_validation' && ! empty($missing)) {
                $missingCount = count($missing);
                $this->issues[$document->uid]['warnings'][] = [
                    'type' => 'missing_required_documents',
                    'message' => "Document in validation but missing {$missingCount} required document(s)",
                ];
            }
        } catch (\Exception $e) {
            // Continue if validation fails
        }
    }

    /**
     * Fix media issues
     */
    protected function fixMediaIssues(Document $document, array $issues): int
    {
        $fixed = 0;

        foreach ($issues as $issue) {
            try {
                switch ($issue['type']) {
                    case 'uploaded_documents_mismatch':
                        // Sync uploaded_documents JSON with actual media
                        $document->syncUploadedDocumentsJson();
                        $fixed++;
                        break;

                    case 'additional_attachments_count_mismatch':
                        // Sync additional_attachments JSON
                        $document->syncAdditionalAttachmentsJson();
                        $fixed++;
                        break;

                    case 'required_documents_mismatch':
                        // Update required_documents JSON
                        $document->updateRequiredDocumentsJson();
                        $fixed++;
                        break;
                }
            } catch (\Exception $e) {
                // If fix fails, continue
            }
        }

        if ($fixed > 0) {
            try {
                $document->save();
            } catch (\Exception $e) {
                // If save fails
            }
        }

        return $fixed;
    }

    /**
     * Display results
     */
    protected function displayResults(array $statistics, bool $fix): void
    {
        $this->newLine(2);
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║              MEDIA VALIDATION RESULTS                      ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line("  Total documents processed: <fg=blue>{$statistics['total']}</fg=blue>");
        $this->line("  ✓ Healthy documents: <fg=green>{$statistics['healthy']}</fg=green>");
        $this->line("  ✗ Documents with issues: <fg=yellow>{$statistics['issues_found']}</fg=yellow>");

        if ($fix && $statistics['issues_fixed'] > 0) {
            $this->line("  ✓ Issues fixed: <fg=green>{$statistics['issues_fixed']}</fg=green>");
        }

        if (! empty($statistics['by_type'])) {
            $this->newLine();
            $this->info('Issues by document type:');
            foreach ($statistics['by_type'] as $type => $count) {
                $this->line("  • $type: <fg=yellow>$count</fg=yellow>");
            }
        }

        // Show sample issues
        if (! empty($this->issues) && count($this->issues) > 0) {
            $this->newLine();
            $this->info('─────────────────────────────────────────────────────────');
            $this->info('SAMPLE ISSUES (showing first 10)');
            $this->info('─────────────────────────────────────────────────────────');
            $this->newLine();

            $count = 0;
            foreach ($this->issues as $uid => $data) {
                if ($count >= 10) {
                    $remaining = count($this->issues) - 10;
                    if ($remaining > 0) {
                        $this->line("  ... and <fg=yellow>$remaining</fg=yellow> more documents");
                    }
                    break;
                }

                $this->line("  <fg=cyan>{$uid}</fg=cyan> ({$data['type']})");
                foreach ($data['issues'] as $issue) {
                    $this->line("    • {$issue['message']}");
                }
                $this->newLine();
                $count++;
            }
        }

        $healthPercent = $statistics['total'] > 0
            ? round(($statistics['healthy'] / $statistics['total']) * 100, 2)
            : 0;

        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info("  Media validation complete - <fg=green>{$healthPercent}%</fg=green> health");

        if (! $fix && $statistics['issues_found'] > 0) {
            $this->info("  Run with --fix to repair <fg=yellow>{$statistics['issues_found']}</fg=yellow> documents");
        }

        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }
}
