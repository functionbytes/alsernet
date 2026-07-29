<?php

namespace Modules\Supplier\Models\Extraction;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Database\Factories\Extraction\ExtractionMappingFactory;
use Modules\Supplier\Models\Source\Source;

class ExtractionMapping extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_extraction_mappings';

    protected $fillable = [
        'uid',
        'source_id',
        'name',
        'source_type',
        'field_mappings',
        'search_config',
        'validation_rules',
        'transform_rules',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'field_mappings' => 'array',
            'search_config' => 'array',
            'validation_rules' => 'array',
            'transform_rules' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): ExtractionMappingFactory
    {
        return ExtractionMappingFactory::new();
    }

    /**
     * Get the source that owns this mapping.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Get all extraction results using this mapping.
     */
    public function results(): HasMany
    {
        return $this->hasMany(ExtractionResult::class, 'mapping_id');
    }

    /**
     * Scope to filter by active mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by inactive mappings.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter by source type.
     */
    public function scopeBySourceType($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Scope to filter by source.
     */
    public function scopeBySource($query, int $sourceId)
    {
        return $query->where('source_id', $sourceId);
    }

    /**
     * Scope to get website mappings.
     */
    public function scopeWebsite($query)
    {
        return $query->where('source_type', 'website');
    }

    /**
     * Scope to get FTP Excel mappings.
     */
    public function scopeFtpExcel($query)
    {
        return $query->where('source_type', 'ftp_excel');
    }

    /**
     * Scope to get FTP CSV mappings.
     */
    public function scopeFtpCsv($query)
    {
        return $query->where('source_type', 'ftp_csv');
    }

    /**
     * Scope to get FTP PDF mappings.
     */
    public function scopeFtpPdf($query)
    {
        return $query->where('source_type', 'ftp_pdf');
    }

    /**
     * Scope to get upload PDF mappings.
     */
    public function scopeUploadPdf($query)
    {
        return $query->where('source_type', 'upload_pdf');
    }

    /**
     * Scope to get upload Excel mappings.
     */
    public function scopeUploadExcel($query)
    {
        return $query->where('source_type', 'upload_excel');
    }

    /**
     * Scope to get API mappings.
     */
    public function scopeApi($query)
    {
        return $query->where('source_type', 'api');
    }

    /**
     * Get field mapping for a specific field.
     */
    public function getFieldMapping(string $field): ?array
    {
        return $this->field_mappings[$field] ?? null;
    }

    /**
     * Check if a specific field is mapped.
     */
    public function hasFieldMapping(string $field): bool
    {
        return isset($this->field_mappings[$field]);
    }

    /**
     * Get all mapped field names.
     */
    public function getMappedFields(): array
    {
        return array_keys($this->field_mappings);
    }

    /**
     * Get validation rule for a specific field.
     */
    public function getValidationRule(string $field): ?array
    {
        return $this->validation_rules[$field] ?? null;
    }

    /**
     * Get transform rule for a specific field.
     */
    public function getTransformRule(string $field): ?array
    {
        return $this->transform_rules[$field] ?? null;
    }

    /**
     * Check if this mapping is for website scraping.
     */
    public function isWebsiteMapping(): bool
    {
        return $this->source_type === 'website';
    }

    /**
     * Check if this mapping is for FTP files.
     */
    public function isFtpMapping(): bool
    {
        return in_array($this->source_type, ['ftp_excel', 'ftp_csv', 'ftp_pdf']);
    }

    /**
     * Check if this mapping is for uploads.
     */
    public function isUploadMapping(): bool
    {
        return in_array($this->source_type, ['upload_pdf', 'upload_excel']);
    }

    /**
     * Check if this mapping is for API.
     */
    public function isApiMapping(): bool
    {
        return $this->source_type === 'api';
    }
}
