<?php

namespace Modules\Supplier\Services\Extraction;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Extraction\ExtractionResult;

/**
 * Content File Storage
 *
 * Saves extracted product content as plain-text files on local disk.
 * Each file represents one product and is named by its SKU/reference.
 * These files serve as context for AI content generation later.
 *
 * Storage path: storage/app/supplier-content/{supplier_id}/{source_id}/{sku}.txt
 */
class ContentFileStorage
{
    private const DISK = 'local';

    private const BASE_DIR = 'supplier-content';

    /**
     * Save extracted product data to a .txt file and return the path.
     */
    public function save(ExtractionResult $result): string
    {
        $data = $result->extracted_data ?? [];

        $filename = $this->buildFilename($result, $data);
        $directory = sprintf('%s/%s/%s', self::BASE_DIR, $result->supplier_id, $result->source_id);
        $path = "{$directory}/{$filename}";

        $content = $this->buildTextContent($data, $result);

        Storage::disk(self::DISK)->put($path, $content);

        return $path;
    }

    /**
     * Read the content of a stored file.
     */
    public function read(string $path): ?string
    {
        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->get($path);
    }

    /**
     * Delete a stored file.
     */
    public function delete(string $path): void
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * List all content files for a supplier/source.
     *
     * @return array<int, string>
     */
    public function listFiles(int $supplierId, int $sourceId): array
    {
        $dir = sprintf('%s/%s/%s', self::BASE_DIR, $supplierId, $sourceId);

        return Storage::disk(self::DISK)->files($dir);
    }

    /**
     * Build the filename: {sku_or_slug}.txt
     */
    private function buildFilename(ExtractionResult $result, array $data): string
    {
        if (! empty($data['reference'])) {
            $slug = Str::slug($data['reference'], '_');

            return "{$slug}.txt";
        }

        if (! empty($data['ean'])) {
            return "{$data['ean']}.txt";
        }

        if (! empty($data['name'])) {
            $slug = Str::slug(mb_substr($data['name'], 0, 60), '_');

            return "{$slug}.txt";
        }

        return "{$result->uid}.txt";
    }

    /**
     * Build readable plain-text content for AI context.
     */
    private function buildTextContent(array $data, ExtractionResult $result): string
    {
        $lines = [];

        $lines[] = '=== PRODUCTO ===';
        $lines[] = '';

        $this->appendLine($lines, 'SKU/Referencia', $data['reference'] ?? null);
        $this->appendLine($lines, 'EAN', $data['ean'] ?? null);
        $this->appendLine($lines, 'Nombre', $data['name'] ?? null);
        $this->appendLine($lines, 'Precio', isset($data['price']) ? $data['price'].' '.($data['currency'] ?? '') : null);
        $this->appendLine($lines, 'Categoría', $data['category'] ?? null);

        if (! empty($data['description'])) {
            $lines[] = '';
            $lines[] = 'Descripción:';
            $lines[] = $data['description'];
        }

        if (! empty($data['attributes']) && is_array($data['attributes'])) {
            $lines[] = '';
            $lines[] = 'Atributos:';
            foreach ($data['attributes'] as $key => $value) {
                if (is_scalar($value) && $value !== null && $value !== '') {
                    $lines[] = "  {$key}: {$value}";
                }
            }
        }

        if (! empty($data['images']) && is_array($data['images'])) {
            $lines[] = '';
            $lines[] = 'Imágenes:';
            foreach (array_slice($data['images'], 0, 10) as $img) {
                $lines[] = "  {$img}";
            }
        }

        $lines[] = '';
        $lines[] = '--- Metadatos ---';
        $this->appendLine($lines, 'URL fuente', $data['source_url'] ?? $result->source_url ?? null);
        $this->appendLine($lines, 'Calidad extracción', $result->extraction_quality ?? null);
        $lines[] = 'Extraído el: '.now()->format('Y-m-d H:i:s');

        return implode("\n", $lines)."\n";
    }

    private function appendLine(array &$lines, string $label, mixed $value): void
    {
        if ($value !== null && $value !== '' && $value !== []) {
            $lines[] = "{$label}: {$value}";
        }
    }
}
