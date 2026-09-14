<?php

namespace Modules\Supplier\Models\Source;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Source\SourceOptionFactory;

class SourceOption extends Model
{
    use HasFactory;

    protected $table = 'supplier_source_options';

    public const TYPE_STRING = 'string';

    public const TYPE_URL = 'url';

    public const TYPE_JSON = 'json';

    public const TYPE_PATH = 'path';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_BOOLEAN = 'boolean';

    protected $fillable = [
        'source_id',
        'option_key',
        'option_value',
        'option_type',
        'is_required',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'is_required' => 'boolean',
    ];

    protected static function newFactory(): SourceOptionFactory
    {
        return SourceOptionFactory::new();
    }

    /**
     * Get the source that owns this option
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Scope: Filter by source
     */
    public function scopeForSource($query, int $sourceId)
    {
        return $query->where('source_id', $sourceId);
    }

    /**
     * Scope: Filter by option key
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('option_key', $key);
    }

    /**
     * Scope: Filter required options
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope: Filter by option type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('option_type', $type);
    }

    /**
     * Helper: Get parsed value based on type
     */
    public function getParsedValue()
    {
        if (is_null($this->option_value)) {
            return null;
        }

        return match ($this->option_type) {
            self::TYPE_JSON => json_decode($this->option_value, true),
            self::TYPE_INTEGER => (int) $this->option_value,
            self::TYPE_BOOLEAN => filter_var($this->option_value, FILTER_VALIDATE_BOOLEAN),
            default => $this->option_value,
        };
    }

    /**
     * Helper: Set value with automatic type handling
     */
    public function setValue($value): void
    {
        $this->option_value = match ($this->option_type) {
            self::TYPE_JSON => is_string($value) ? $value : json_encode($value),
            self::TYPE_BOOLEAN => $value ? 'true' : 'false',
            default => (string) $value,
        };

        $this->save();
    }

    /**
     * Helper: Check if option is required
     */
    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Helper: Check if value is valid JSON
     */
    public function isValidJson(): bool
    {
        if ($this->option_type !== self::TYPE_JSON) {
            return false;
        }

        if (is_null($this->option_value)) {
            return false;
        }

        json_decode($this->option_value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Helper: Get option display name
     */
    public function getDisplayNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->option_key));
    }

    /**
     * Helper: Validate option value format based on type
     */
    public function validateValue(): bool
    {
        if (is_null($this->option_value)) {
            return ! $this->is_required;
        }

        return match ($this->option_type) {
            self::TYPE_URL => filter_var($this->option_value, FILTER_VALIDATE_URL) !== false,
            self::TYPE_JSON => $this->isValidJson(),
            self::TYPE_INTEGER => is_numeric($this->option_value),
            self::TYPE_PATH => ! empty($this->option_value),
            default => true,
        };
    }
}
