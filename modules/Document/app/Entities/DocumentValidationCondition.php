<?php

namespace Modules\Document\Entities;

use Modules\Document\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class DocumentValidationCondition extends Model
{
    protected $table = 'document_validation_conditions';

    use HasUid;

    public const TYPE_SALE_TYPE_MATCH = 'sale_type_match';

    public const TYPE_MODEL_FIELD = 'model_field';

    public const TYPE_CUSTOM_EXPRESSION = 'custom_expression';

    public const AVAILABLE_TYPES = [
        self::TYPE_SALE_TYPE_MATCH => 'Coincidencia de etiquetas',
        self::TYPE_MODEL_FIELD => 'Campo del modelo',
        self::TYPE_CUSTOM_EXPRESSION => 'Expresión personalizada',
    ];

    protected $fillable = [
        'uid',
        'key',
        'condition_type',
        'name',
        'description',
        'sale_types',
        'model_field',
        'expected_value',
        'validation_expression',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sale_types' => 'array',
            'expected_value' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function matches(string $saleType): bool
    {
        if (! $this->is_active || empty($this->sale_types)) {
            return false;
        }

        return in_array($saleType, $this->sale_types);
    }

    public static function getByKey(string $key): ?self
    {
        return cache()->remember("validation_condition:{$key}", 3600, function () use ($key) {
            return self::active()->byKey($key)->first();
        });
    }

    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            cache()->forget("validation_condition:{$key}");
        } else {
            // Clear all condition caches
            $keys = self::pluck('key');
            foreach ($keys as $conditionKey) {
                cache()->forget("validation_condition:{$conditionKey}");
            }
        }
    }

    protected static function booted(): void
    {
        static::saved(function ($condition) {
            self::clearCache($condition->key);
        });

        static::deleted(function ($condition) {
            self::clearCache($condition->key);
        });
    }
}
