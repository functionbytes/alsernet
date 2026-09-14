<?php

namespace Modules\Supplier\Models\Source;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Source\SourceFileFactory;
use Modules\Supplier\Models\Extraction\ExtractionResult;

class SourceFile extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_source_files';

    protected static function newFactory(): SourceFileFactory
    {
        return SourceFileFactory::new();
    }

    public const FILE_TYPE_PDF = 'pdf';

    public const FILE_TYPE_EXCEL = 'excel';

    public const FILE_TYPE_WORD = 'word';

    public const FILE_TYPE_IMAGE = 'image';

    public const FILE_TYPE_CSV = 'csv';

    public const FILE_TYPE_HTML = 'html';

    public const FILE_TYPE_OTHER = 'other';

    public const ORIGIN_FTP = 'ftp';

    public const ORIGIN_SFTP = 'sftp';

    public const ORIGIN_UPLOAD = 'upload';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uid',
        'source_id',
        'supplier_id',
        'original_name',
        'stored_path',
        'file_type',
        'file_size',
        'mime_type',
        'origin',
        'extraction_status',
        'extracted_at',
        'extraction_result_id',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'extracted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the source that owns this file.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Get the extraction result linked to this file.
     */
    public function extractionResult(): BelongsTo
    {
        return $this->belongsTo(ExtractionResult::class, 'extraction_result_id');
    }

    // --- Scopes ---

    public function scopePending($query)
    {
        return $query->where('extraction_status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('extraction_status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('extraction_status', self::STATUS_FAILED);
    }

    public function scopeBySource($query, int $sourceId)
    {
        return $query->where('source_id', $sourceId);
    }

    public function scopeByType($query, string $fileType)
    {
        return $query->where('file_type', $fileType);
    }

    public function scopeByOrigin($query, string $origin)
    {
        return $query->where('origin', $origin);
    }

    // --- Helper methods ---

    public function isPdf(): bool
    {
        return $this->file_type === self::FILE_TYPE_PDF;
    }

    public function isExcel(): bool
    {
        return $this->file_type === self::FILE_TYPE_EXCEL;
    }

    public function isWord(): bool
    {
        return $this->file_type === self::FILE_TYPE_WORD;
    }

    public function isImage(): bool
    {
        return $this->file_type === self::FILE_TYPE_IMAGE;
    }

    public function isCsv(): bool
    {
        return $this->file_type === self::FILE_TYPE_CSV;
    }

    public function isPending(): bool
    {
        return $this->extraction_status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->extraction_status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->extraction_status === self::STATUS_FAILED;
    }
}
