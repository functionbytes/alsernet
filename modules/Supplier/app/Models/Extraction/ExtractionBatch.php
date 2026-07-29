<?php

namespace Modules\Supplier\Models\Extraction;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Supplier\Database\Factories\Extraction\ExtractionBatchFactory;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Supplier\Supplier;

class ExtractionBatch extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_extraction_batches';

    protected $fillable = [
        'uid',
        'supplier_id',
        'source_id',
        'batch_date',
        'batch_type',
        'status',
        'total_items',
        'new_items',
        'updated_items',
        'unchanged_items',
        'failed_items',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'batch_date' => 'date',
            'total_items' => 'integer',
            'new_items' => 'integer',
            'updated_items' => 'integer',
            'unchanged_items' => 'integer',
            'failed_items' => 'integer',
            'summary' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Boot the model and auto-generate UID.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = Str::ulid()->toBase32();
            }
        });
    }

    /**
     * Get the supplier that owns this batch.
     */
    protected static function newFactory(): ExtractionBatchFactory
    {
        return ExtractionBatchFactory::new();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the source for this batch (if single source).
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Get all extraction results in this batch.
     */
    public function results(): HasMany
    {
        return $this->hasMany(ExtractionResult::class, 'batch_id', 'uid');
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
     * Scope to filter by batch date.
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('batch_date', $date);
    }

    /**
     * Scope to filter by batch type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('batch_type', $type);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending batches.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get processing batches.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to get completed batches.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed batches.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get daily batches.
     */
    public function scopeDaily($query)
    {
        return $query->where('batch_type', 'daily');
    }

    /**
     * Scope to get manual batches.
     */
    public function scopeManual($query)
    {
        return $query->where('batch_type', 'manual');
    }

    /**
     * Scope to get incremental batches.
     */
    public function scopeIncremental($query)
    {
        return $query->where('batch_type', 'incremental');
    }

    /**
     * Scope to get full sync batches.
     */
    public function scopeFullSync($query)
    {
        return $query->where('batch_type', 'full_sync');
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('batch_date', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order by oldest.
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('batch_date', 'asc')->orderBy('created_at', 'asc');
    }

    /**
     * Scope to get batches within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('batch_date', [$startDate, $endDate]);
    }

    /**
     * Mark the batch as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the batch as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the batch as failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Update batch metrics from results.
     */
    public function updateMetrics(): void
    {
        $results = $this->results()->get();

        $this->update([
            'total_items' => $results->count(),
            'new_items' => $results->where('status', 'new')->count(),
            'updated_items' => $results->where('status', 'updated')->count(),
            'unchanged_items' => $results->where('status', 'existing')->count(),
            'failed_items' => $results->where('status', 'error')->count(),
        ]);
    }

    /**
     * Generate summary statistics.
     */
    public function generateSummary(): array
    {
        $results = $this->results()->get();

        $qualityCounts = [
            'complete' => $results->where('extraction_quality', 'complete')->count(),
            'partial' => $results->where('extraction_quality', 'partial')->count(),
            'minimal' => $results->where('extraction_quality', 'minimal')->count(),
            'failed' => $results->where('extraction_quality', 'failed')->count(),
        ];

        $fieldCoverage = [];
        if ($results->isNotEmpty()) {
            $allFields = collect();
            foreach ($results as $result) {
                $allFields = $allFields->merge(array_keys($result->extracted_data));
            }

            $uniqueFields = $allFields->unique();
            foreach ($uniqueFields as $field) {
                $presentCount = $results->filter(function ($result) use ($field) {
                    return isset($result->extracted_data[$field]) && $result->extracted_data[$field] !== null;
                })->count();

                $fieldCoverage[$field] = [
                    'present' => $presentCount,
                    'total' => $results->count(),
                    'percentage' => ($presentCount / $results->count()) * 100,
                ];
            }
        }

        return [
            'quality_distribution' => $qualityCounts,
            'field_coverage' => $fieldCoverage,
            'total_processed' => $results->count(),
            'success_rate' => $results->count() > 0
                ? (($results->count() - $this->failed_items) / $results->count()) * 100
                : 0,
            'average_completion' => $results->avg(function ($result) {
                return $result->getCompletionPercentage();
            }),
        ];
    }

    /**
     * Update summary with current statistics.
     */
    public function updateSummary(): void
    {
        $this->update([
            'summary' => $this->generateSummary(),
        ]);
    }

    /**
     * Get the success rate percentage.
     */
    public function getSuccessRate(): float
    {
        if ($this->total_items === 0) {
            return 0.0;
        }

        return (($this->total_items - $this->failed_items) / $this->total_items) * 100;
    }

    /**
     * Get the processing duration in seconds.
     */
    public function getProcessingDuration(): ?int
    {
        if ($this->started_at === null || $this->completed_at === null) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Check if the batch is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the batch is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the batch is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the batch failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if this is a daily batch.
     */
    public function isDaily(): bool
    {
        return $this->batch_type === 'daily';
    }

    /**
     * Check if this is a manual batch.
     */
    public function isManual(): bool
    {
        return $this->batch_type === 'manual';
    }

    /**
     * Check if this is an incremental batch.
     */
    public function isIncremental(): bool
    {
        return $this->batch_type === 'incremental';
    }

    /**
     * Check if this is a full sync batch.
     */
    public function isFullSync(): bool
    {
        return $this->batch_type === 'full_sync';
    }
}
