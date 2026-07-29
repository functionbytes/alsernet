<?php

namespace Modules\Supplier\Models\Source;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Source\SourceHealthHistoryFactory;

class SourceHealthHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'supplier_source_health_history';

    protected $fillable = [
        'source_id',
        'check_type',
        'is_success',
        'status_code',
        'response_time_ms',
        'error_type',
        'error_message',
        'page_size_bytes',
        'products_found',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_success' => 'boolean',
            'status_code' => 'integer',
            'response_time_ms' => 'integer',
            'page_size_bytes' => 'integer',
            'products_found' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SourceHealthHistoryFactory
    {
        return SourceHealthHistoryFactory::new();
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('is_success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('is_success', false);
    }

    public function scopeByCheckType($query, string $type)
    {
        return $query->where('check_type', $type);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('checked_at', '>=', now()->subHours($hours));
    }

    public function scopeLastWeek($query)
    {
        return $query->where('checked_at', '>=', now()->subWeek());
    }

    public function scopeLastMonth($query)
    {
        return $query->where('checked_at', '>=', now()->subMonth());
    }

    public function isConnectivityCheck(): bool
    {
        return $this->check_type === 'connectivity';
    }

    public function isAuthenticationCheck(): bool
    {
        return $this->check_type === 'authentication';
    }

    public function isStructureCheck(): bool
    {
        return $this->check_type === 'structure';
    }

    public function isContentCheck(): bool
    {
        return $this->check_type === 'content';
    }

    public static function recordCheck(
        int $sourceId,
        string $checkType,
        bool $isSuccess,
        ?int $statusCode = null,
        ?int $responseTimeMs = null,
        ?string $errorType = null,
        ?string $errorMessage = null,
        ?int $pageSizeBytes = null,
        ?int $productsFound = null
    ): self {
        return static::create([
            'source_id' => $sourceId,
            'check_type' => $checkType,
            'is_success' => $isSuccess,
            'status_code' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'page_size_bytes' => $pageSizeBytes,
            'products_found' => $productsFound,
            'checked_at' => now(),
        ]);
    }
}
