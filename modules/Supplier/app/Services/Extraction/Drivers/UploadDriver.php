<?php

namespace Modules\Supplier\Services\Extraction\Drivers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Supplier\Models\Extraction\ExtractionBatch;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceFile;
use Modules\Supplier\Services\Extraction\Contracts\SourceDriverInterface;
use Modules\Supplier\Services\Extraction\FileExtractionPipeline;

/**
 * Upload Driver
 *
 * Processes files that have already been uploaded locally and are stored
 * in supplier_source_files with status 'pending'.
 */
class UploadDriver implements SourceDriverInterface
{
    public function __construct(
        private readonly FileExtractionPipeline $pipeline
    ) {}

    public function supports(string $sourceType): bool
    {
        return in_array($sourceType, ['upload', Source::SOURCE_TYPE_FILE], true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extract(Source $source, ExtractionBatch $batch): array
    {
        $pendingFiles = SourceFile::where('source_id', $source->id)
            ->where('extraction_status', SourceFile::STATUS_PENDING)
            ->get();

        if ($pendingFiles->isEmpty()) {
            Log::info('UploadDriver: no pending files to process', ['source_id' => $source->id]);

            return [];
        }

        $products = [];

        foreach ($pendingFiles as $sourceFile) {
            try {
                $products = array_merge($products, $this->processSourceFile($sourceFile));
            } catch (\Exception $e) {
                Log::error('UploadDriver: failed to process file', [
                    'source_file_id' => $sourceFile->id,
                    'error' => $e->getMessage(),
                ]);

                $sourceFile->update([
                    'extraction_status' => SourceFile::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return $products;
    }

    /**
     * Process a single pending SourceFile.
     *
     * @return array<int, array<string, mixed>>
     */
    private function processSourceFile(SourceFile $sourceFile): array
    {
        $sourceFile->update(['extraction_status' => SourceFile::STATUS_PROCESSING]);

        $absolutePath = Storage::path($sourceFile->stored_path);

        if (! file_exists($absolutePath)) {
            $sourceFile->update([
                'extraction_status' => SourceFile::STATUS_FAILED,
                'error_message' => 'File not found at stored path',
            ]);

            Log::warning('UploadDriver: stored file not found', [
                'source_file_id' => $sourceFile->id,
                'path' => $sourceFile->stored_path,
            ]);

            return [];
        }

        $products = $this->pipeline->process($absolutePath, $sourceFile->file_type ?? '');

        $sourceFile->update([
            'extraction_status' => SourceFile::STATUS_COMPLETED,
            'extracted_at' => now(),
            'metadata' => array_merge($sourceFile->metadata ?? [], ['product_count' => count($products)]),
        ]);

        foreach ($products as &$product) {
            $product['source_file'] = $sourceFile->original_name;
        }
        unset($product);

        return $products;
    }
}
