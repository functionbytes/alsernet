<?php

namespace Modules\Supplier\Services\Extraction\Contracts;

use Modules\Supplier\Models\Extraction\ExtractionBatch;
use Modules\Supplier\Models\Source\Source;

interface SourceDriverInterface
{
    /**
     * Extract products from the source.
     *
     * @return array<int, array{
     *     reference: string|null,
     *     ean: string|null,
     *     name: string|null,
     *     description: string|null,
     *     price: float|null,
     *     currency: string|null,
     *     images: array,
     *     attributes: array,
     *     category: string|null,
     * }>
     */
    public function extract(Source $source, ExtractionBatch $batch): array;

    /**
     * Check if this driver supports the given source type.
     */
    public function supports(string $sourceType): bool;
}
