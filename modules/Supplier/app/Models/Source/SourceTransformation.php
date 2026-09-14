<?php

namespace Modules\Supplier\Models\Source;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Database\Factories\Source\SourceTransformationFactory;

class SourceTransformation extends Model
{
    use HasFactory, HasUid;

    /**
     * Transformation types that are accepted by the schema but not yet implemented.
     * When encountered at runtime the input value is passed through unchanged.
     */
    public const DEPRECATED_TYPES = ['formula', 'lookup', 'custom_function'];

    protected $table = 'supplier_source_transformations';

    protected $fillable = [
        'source_id',
        'name',
        'description',
        'field_name',
        'apply_order',
        'transformation_type',
        'transformation_config',
        'apply_condition',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'transformation_config' => 'array',
            'apply_condition' => 'array',
            'is_enabled' => 'boolean',
            'apply_order' => 'integer',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('apply_order', 'asc');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('transformation_type', $type);
    }

    public function scopeForField($query, string $fieldName)
    {
        return $query->where(function ($q) use ($fieldName) {
            $q->where('field_name', $fieldName)
                ->orWhereNull('field_name');
        });
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('field_name');
    }

    public function isRegexReplace(): bool
    {
        return $this->transformation_type === 'regex_replace';
    }

    public function isRegexExtract(): bool
    {
        return $this->transformation_type === 'regex_extract';
    }

    public function isMapping(): bool
    {
        return $this->transformation_type === 'mapping';
    }

    public function isFormula(): bool
    {
        return $this->transformation_type === 'formula';
    }

    public function isLookup(): bool
    {
        return $this->transformation_type === 'lookup';
    }

    public function isSplit(): bool
    {
        return $this->transformation_type === 'split';
    }

    public function isJoin(): bool
    {
        return $this->transformation_type === 'join';
    }

    public function isFormat(): bool
    {
        return $this->transformation_type === 'format';
    }

    public function isCustomFunction(): bool
    {
        return $this->transformation_type === 'custom_function';
    }

    public function shouldApply(array $data): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if (! $this->apply_condition) {
            return true;
        }

        return $this->evaluateCondition($data, $this->apply_condition);
    }

    /**
     * Evaluate a flat key-value condition map against the given data row.
     * All keys must match their expected values for the condition to pass.
     */
    protected function evaluateCondition(array $data, array $condition): bool
    {
        foreach ($condition as $field => $expectedValue) {
            if (! isset($data[$field]) || $data[$field] !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    public function apply(mixed $value): mixed
    {
        if (in_array($this->transformation_type, self::DEPRECATED_TYPES, true)) {
            Log::warning('Deprecated transformation type skipped', [
                'type' => $this->transformation_type,
                'transformation_id' => $this->id,
            ]);

            return $value;
        }

        return match ($this->transformation_type) {
            'regex_replace' => $this->applyRegexReplace($value),
            'regex_extract' => $this->applyRegexExtract($value),
            'mapping' => $this->applyMapping($value),
            'split' => $this->applySplit($value),
            'join' => $this->applyJoin($value),
            'format' => $this->applyFormat($value),
            default => $value,
        };
    }

    protected static function newFactory(): SourceTransformationFactory
    {
        return SourceTransformationFactory::new();
    }

    protected function applyRegexReplace(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $patterns = $this->transformation_config['patterns'] ?? [];
        foreach ($patterns as $pattern) {
            $value = preg_replace($pattern['find'], $pattern['replace'], $value);
        }

        return $value;
    }

    protected function applyRegexExtract(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $pattern = $this->transformation_config['pattern'] ?? null;
        if (! $pattern || ! preg_match($pattern, $value, $matches)) {
            return $value;
        }

        return $matches[1] ?? $value;
    }

    protected function applyMapping(mixed $value): mixed
    {
        $mapping = $this->transformation_config['mapping'] ?? [];
        $default = $this->transformation_config['default'] ?? $value;

        return $mapping[$value] ?? $default;
    }

    /**
     * @deprecated Formula evaluation is not yet implemented; the value is passed through unchanged.
     */
    protected function applyFormula(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @deprecated Lookup execution is not yet implemented; the value is passed through unchanged.
     */
    protected function applyLookup(mixed $value): mixed
    {
        return $value;
    }

    protected function applySplit(mixed $value): array
    {
        if (! is_string($value)) {
            return (array) $value;
        }

        $delimiter = $this->transformation_config['delimiter'] ?? ',';

        return explode($delimiter, $value);
    }

    protected function applyJoin(mixed $value): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }

        $delimiter = $this->transformation_config['delimiter'] ?? ',';

        return implode($delimiter, $value);
    }

    protected function applyFormat(mixed $value): string
    {
        $format = $this->transformation_config['format'] ?? '%s';

        return sprintf($format, $value);
    }

    /**
     * @deprecated Custom function execution is not yet implemented; the value is passed through unchanged.
     */
    protected function applyCustomFunction(mixed $value): mixed
    {
        return $value;
    }
}
