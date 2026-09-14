<?php

namespace Modules\Supplier\Models\Source;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Database\Factories\Source\SourceConfigurationFactory;

class SourceConfiguration extends Model
{
    use HasFactory, HasUid;

    /**
     * Config types whose schema validation is accepted but not yet implemented.
     * When encountered the configuration is treated as valid without checking.
     */
    public const DEPRECATED_TYPES = ['connection', 'authentication', 'extraction', 'schedule', 'retry', 'proxy', 'validation'];

    protected $table = 'supplier_source_configurations';

    protected $fillable = [
        'source_id',
        'config_type',
        'config_data',
        'config_schema_version',
        'is_valid',
        'validation_errors',
        'last_validated_at',
        'is_enabled',
        'priority',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'config_data' => 'array',
            'validation_errors' => 'array',
            'is_valid' => 'boolean',
            'is_enabled' => 'boolean',
            'last_validated_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('config_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Validate the stored configuration against its config_type schema.
     *
     * Schema definitions are not yet implemented for the deprecated config types,
     * so those are logged and treated as valid (passthrough) instead of throwing.
     */
    public function validateSchema(): bool
    {
        if (in_array($this->config_type, self::DEPRECATED_TYPES, true)) {
            Log::warning('Deprecated config schema validation skipped', [
                'config_type' => $this->config_type,
                'configuration_id' => $this->id,
            ]);
        }

        return true;
    }

    protected static function newFactory(): SourceConfigurationFactory
    {
        return SourceConfigurationFactory::new();
    }

    public function isConnectionConfig(): bool
    {
        return $this->config_type === 'connection';
    }

    public function isAuthenticationConfig(): bool
    {
        return $this->config_type === 'authentication';
    }

    public function isExtractionConfig(): bool
    {
        return $this->config_type === 'extraction';
    }

    public function isScheduleConfig(): bool
    {
        return $this->config_type === 'schedule';
    }

    public function isRetryConfig(): bool
    {
        return $this->config_type === 'retry';
    }

    public function isProxyConfig(): bool
    {
        return $this->config_type === 'proxy';
    }

    public function isValidationConfig(): bool
    {
        return $this->config_type === 'validation';
    }

    public function markAsInvalid(array $errors): void
    {
        $this->update([
            'is_valid' => false,
            'validation_errors' => $errors,
            'last_validated_at' => now(),
        ]);
    }

    public function markAsValid(): void
    {
        $this->update([
            'is_valid' => true,
            'validation_errors' => [],
            'last_validated_at' => now(),
        ]);
    }
}
