<?php

namespace Modules\Supplier\Models\Extraction;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Supplier\Database\Factories\Extraction\ExtractionResultFactory;
use Modules\Supplier\Models\Automation\AutomationExecution;
/**
 * Supplier Extraction Result Model
 *
 * Stores extracted product data from any source type in a unified structure.
 * Tracks changes via hash comparison and maintains extraction quality metrics.
 *
 * @property int $id
 * @property string $uid
 * @property int $supplier_id
 * @property int $source_id
 * @property int $mapping_id
 * @property int|null $execution_id
 * @property string|null $batch_id
 * @property Carbon|null $batch_date
 * @property string|null $reference
 * @property string|null $ean
 * @property string|null $source_url
 * @property string|null $source_file
 * @property array $extracted_data
 * @property array|null $raw_data
 * @property string $extraction_quality
 * @property array|null $missing_fields
 * @property string $status
 * @property string|null $hash
 * @property string|null $previous_hash
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Supplier $supplier
 * @property-read Source $source
 * @property-read ExtractionMapping $mapping
 * @property-read AutomationExecution|null $execution
 * @property-read ExtractionBatch|null $batch
 */
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Supplier\Supplier;

class ExtractionResult extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_extraction_results';

    protected $fillable = [
        'uid',
        'supplier_id',
        'source_id',
        'mapping_id',
        'execution_id',
        'batch_id',
        'batch_date',
        'reference',
        'ean',
        'source_url',
        'source_file',
        'extracted_data',
        'raw_data',
        'extraction_quality',
        'missing_fields',
        'status',
        'hash',
        'previous_hash',
    ];

    protected function casts(): array
    {
        return [
            'batch_date' => 'date',
            'extracted_data' => 'array',
            'raw_data' => 'array',
            'missing_fields' => 'array',
        ];
    }

    /**
     * Get the supplier that owns this result.
     */
    protected static function newFactory(): ExtractionResultFactory
    {
        return ExtractionResultFactory::new();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the source that owns this result.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Get the mapping used for this extraction.
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(ExtractionMapping::class, 'mapping_id');
    }

    /**
     * Get the execution that created this result.
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(AutomationExecution::class, 'execution_id');
    }

    /**
     * Get the batch this result belongs to.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ExtractionBatch::class, 'batch_id', 'uid');
    }

    /**
     * Scope to filter by supplier.
     */
    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope to filter by source.
     */
    public function scopeBySource($query, int $sourceId)
    {
        return $query->where('source_id', $sourceId);
    }

    /**
     * Scope to filter by batch.
     */
    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to filter by batch date.
     */
    public function scopeByBatchDate($query, $date)
    {
        return $query->whereDate('batch_date', $date);
    }

    /**
     * Scope to filter by reference.
     */
    public function scopeByReference($query, string $reference)
    {
        return $query->where('reference', $reference);
    }

    /**
     * Scope to filter by EAN.
     */
    public function scopeByEan($query, string $ean)
    {
        return $query->where('ean', $ean);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get new items.
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope to get existing items.
     */
    public function scopeExisting($query)
    {
        return $query->where('status', 'existing');
    }

    /**
     * Scope to get updated items.
     */
    public function scopeUpdated($query)
    {
        return $query->where('status', 'updated');
    }

    /**
     * Scope to get error items.
     */
    public function scopeError($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope to filter by extraction quality.
     */
    public function scopeByQuality($query, string $quality)
    {
        return $query->where('extraction_quality', $quality);
    }

    /**
     * Scope to get complete extractions.
     */
    public function scopeComplete($query)
    {
        return $query->where('extraction_quality', 'complete');
    }

    /**
     * Scope to get partial extractions.
     */
    public function scopePartial($query)
    {
        return $query->where('extraction_quality', 'partial');
    }

    /**
     * Scope to get minimal extractions.
     */
    public function scopeMinimal($query)
    {
        return $query->where('extraction_quality', 'minimal');
    }

    /**
     * Scope to get failed extractions.
     */
    public function scopeFailed($query)
    {
        return $query->where('extraction_quality', 'failed');
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order by oldest.
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Generate content hash from extracted data.
     * Excludes volatile fields like price and stock.
     */
    public static function generateContentHash(array $data): string
    {
        $hashableFields = [
            'product_name',
            'short_description',
            'long_description',
            'specifications',
            'features',
            'images',
        ];

        $hashData = array_intersect_key($data, array_flip($hashableFields));

        return md5(json_encode($hashData, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Update the hash for this result.
     */
    public function updateHash(): void
    {
        $this->previous_hash = $this->hash;
        $this->hash = static::generateContentHash($this->extracted_data);
    }

    /**
     * Check if the content has changed compared to previous hash.
     */
    public function hasChanged(): bool
    {
        if ($this->previous_hash === null) {
            return true;
        }

        return $this->hash !== $this->previous_hash;
    }

    /**
     * Get a specific field from extracted data.
     */
    public function getExtractedField(string $field, $default = null)
    {
        return $this->extracted_data[$field] ?? $default;
    }

    /**
     * Check if a field exists in extracted data.
     */
    public function hasExtractedField(string $field): bool
    {
        return isset($this->extracted_data[$field]);
    }

    /**
     * Get the product name from extracted data.
     */
    public function getProductName(): ?string
    {
        return $this->getExtractedField('product_name');
    }

    /**
     * Get the price from extracted data.
     */
    public function getPrice(): ?float
    {
        $price = $this->getExtractedField('price');

        return $price !== null ? (float) $price : null;
    }

    /**
     * Get the stock from extracted data.
     */
    public function getStock(): ?int
    {
        $stock = $this->getExtractedField('stock');

        return $stock !== null ? (int) $stock : null;
    }

    /**
     * Get images from extracted data.
     */
    public function getImages(): array
    {
        return $this->getExtractedField('images', []);
    }

    /**
     * Check if this is a new product.
     */
    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    /**
     * Check if this is an existing product.
     */
    public function isExisting(): bool
    {
        return $this->status === 'existing';
    }

    /**
     * Check if this product was updated.
     */
    public function isUpdated(): bool
    {
        return $this->status === 'updated';
    }

    /**
     * Check if this extraction has errors.
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Check if extraction is complete.
     */
    public function isComplete(): bool
    {
        return $this->extraction_quality === 'complete';
    }

    /**
     * Check if extraction is partial.
     */
    public function isPartial(): bool
    {
        return $this->extraction_quality === 'partial';
    }

    /**
     * Check if extraction is minimal.
     */
    public function isMinimal(): bool
    {
        return $this->extraction_quality === 'minimal';
    }

    /**
     * Check if extraction failed.
     */
    public function isFailed(): bool
    {
        return $this->extraction_quality === 'failed';
    }

    /**
     * Get the completion percentage based on missing fields.
     */
    public function getCompletionPercentage(): float
    {
        if (empty($this->missing_fields)) {
            return 100.0;
        }

        $totalFields = count($this->extracted_data) + count($this->missing_fields);
        $extractedFields = count($this->extracted_data);

        return ($extractedFields / $totalFields) * 100;
    }
}
