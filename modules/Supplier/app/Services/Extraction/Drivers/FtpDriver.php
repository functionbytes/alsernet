<?php

namespace Modules\Supplier\Services\Extraction\Drivers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Extraction\ExtractionBatch;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceFile;
use Modules\Supplier\Services\Extraction\Contracts\SourceDriverInterface;
use Modules\Supplier\Services\Extraction\FileExtractionPipeline;

/**
 * FTP/SFTP Driver
 *
 * Connects to an FTP or SFTP server, lists files in the configured directory,
 * downloads them to a temporary local path and runs them through the
 * FileExtractionPipeline.
 */
class FtpDriver implements SourceDriverInterface
{
    public function __construct(
        private readonly FileExtractionPipeline $pipeline
    ) {}

    public function supports(string $sourceType): bool
    {
        return in_array($sourceType, [Source::SOURCE_TYPE_FTP, 'sftp'], true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extract(Source $source, ExtractionBatch $batch): array
    {
        $config = $this->getConnectionConfig($source);

        if (empty($config)) {
            Log::warning('FtpDriver: no connection configuration found', ['source_id' => $source->id]);

            return [];
        }

        try {
            $disk = $this->buildDisk($source->source_type, $config);
        } catch (\Exception $e) {
            Log::error('FtpDriver: failed to build filesystem', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $directory = $config['directory'] ?? '/';
        $pattern = $config['file_pattern'] ?? '*';

        try {
            $allFiles = $disk->files($directory);
        } catch (\Exception $e) {
            Log::error('FtpDriver: failed to list files', [
                'source_id' => $source->id,
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $files = $this->filterByPattern($allFiles, $pattern);
        $products = [];
        $localDir = "supplier-files/{$source->supplier_id}";

        foreach ($files as $remoteFile) {
            try {
                $products = array_merge($products, $this->processRemoteFile(
                    $disk,
                    $remoteFile,
                    $source,
                    $batch,
                    $localDir
                ));
            } catch (\Exception $e) {
                Log::error('FtpDriver: failed to process file', [
                    'source_id' => $source->id,
                    'file' => $remoteFile,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $products;
    }

    /**
     * Download a remote file, extract products, then clean up.
     *
     * @return array<int, array<string, mixed>>
     */
    private function processRemoteFile(
        $disk,
        string $remoteFile,
        Source $source,
        ExtractionBatch $batch,
        string $localDir
    ): array {
        $fileName = basename($remoteFile);
        $storedName = Str::uuid().'_'.$fileName;
        $storedPath = "{$localDir}/{$storedName}";

        // Skip already-completed files with the same name
        $alreadyDone = SourceFile::where('source_id', $source->id)
            ->where('original_name', $fileName)
            ->where('extraction_status', SourceFile::STATUS_COMPLETED)
            ->exists();

        if ($alreadyDone) {
            Log::debug('FtpDriver: skipping already-processed file', ['file' => $fileName]);

            return [];
        }

        // Download
        $content = $disk->get($remoteFile);
        Storage::put($storedPath, $content);
        $absolutePath = Storage::path($storedPath);

        $fileType = $this->pipeline->detectFileType($absolutePath);

        $sourceFile = SourceFile::create([
            'source_id' => $source->id,
            'supplier_id' => $source->supplier_id,
            'original_name' => $fileName,
            'stored_path' => $storedPath,
            'file_type' => $fileType,
            'file_size' => Storage::size($storedPath),
            'origin' => $source->source_type,
            'extraction_status' => SourceFile::STATUS_PROCESSING,
        ]);

        try {
            $products = $this->pipeline->process($absolutePath, $fileType);

            $sourceFile->update([
                'extraction_status' => SourceFile::STATUS_COMPLETED,
                'extracted_at' => now(),
                'metadata' => ['product_count' => count($products)],
            ]);
        } catch (\Exception $e) {
            $sourceFile->update([
                'extraction_status' => SourceFile::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
            $products = [];
        } finally {
            Storage::delete($storedPath);
        }

        // Tag products with source file reference
        foreach ($products as &$product) {
            $product['source_file'] = $fileName;
        }
        unset($product);

        return $products;
    }

    /**
     * Build a Laravel Storage disk for FTP or SFTP.
     *
     * @param  array<string, mixed>  $config
     */
    private function buildDisk(string $sourceType, array $config): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $driver = $sourceType === 'sftp' ? 'sftp' : 'ftp';
        $defaultPort = $sourceType === 'sftp' ? 22 : 21;

        $diskConfig = [
            'driver' => $driver,
            'host' => $config['host'],
            'username' => $config['username'],
            'password' => $config['password'],
            'port' => (int) ($config['port'] ?? $defaultPort),
            'root' => $config['directory'] ?? '/',
        ];

        return Storage::build($diskConfig);
    }

    /**
     * Filter file list by glob-style pattern.
     *
     * @param  array<int, string>  $files
     * @return array<int, string>
     */
    private function filterByPattern(array $files, string $pattern): array
    {
        if ($pattern === '*') {
            return $files;
        }

        return array_values(array_filter($files, fn ($file) => fnmatch($pattern, basename($file))));
    }

    /**
     * Get connection config from source configurations.
     *
     * @return array<string, mixed>
     */
    private function getConnectionConfig(Source $source): array
    {
        $config = $source->configurations()
            ->where('config_type', 'connection')
            ->where('is_enabled', true)
            ->first();

        return $config?->config_data ?? [];
    }
}
