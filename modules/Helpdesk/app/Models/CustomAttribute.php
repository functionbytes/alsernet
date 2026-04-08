<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class CustomAttribute extends Model
{
    public const MODEL_TYPE = 'attribute';

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_attributes';

    // Permission constants
    public const PERMISSION_USER_CAN_VIEW = 'userCanView';

    public const PERMISSION_USER_CAN_EDIT = 'userCanEdit';

    public const PERMISSION_AGENT_CAN_EDIT = 'agentCanEdit';

    protected $fillable = [
        'name',
        'key',
        'type',
        'format',
        'required',
        'permission',
        'description',
        'customer_name',
        'customer_description',
        'config',
        'internal',
        'materialized',
        'active',
    ];

    protected $casts = [
        'required' => 'boolean',
        'config' => 'array',
        'internal' => 'boolean',
        'materialized' => 'boolean',
        'active' => 'boolean',
    ];

    protected $hidden = ['pivot'];

    /**
     * Apply global scope to only show active attributes by default.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('active', function ($builder) {
            $builder->where('active', true);
        });
    }

    /**
     * Get and cast the value accessor.
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($original, $attributes) {
                $original = isset($this->original['pivot_value'])
                    ? $this->original['pivot_value']
                    : $original;

                return match ($attributes['format']) {
                    'number' => (int) $original,
                    'switch', 'rating' => (bool) $original,
                    'checkboxGroup' => json_decode($original, true),
                    default => $original,
                };
            },
        );
    }

    /**
     * Cast value for storing in database.
     */
    public static function castValueForStoring(mixed $value, string $format): mixed
    {
        return match ($format) {
            'number' => (int) $value,
            'switch', 'rating' => (bool) $value,
            'checkboxGroup' => json_encode($value),
            default => $value,
        };
    }

    /**
     * Filterable fields.
     */
    public static function filterableFields(): array
    {
        return ['id', 'created_at', 'updated_at', 'type', 'format', 'active'];
    }

    /**
     * Convert to array with value if present.
     */
    public function toArray()
    {
        $array = parent::toArray();

        if (
            isset($this->attributes['value']) ||
            isset($this->original['pivot_value'])
        ) {
            $array['value'] = $this->value;
        }

        return $array;
    }

    /**
     * Get compact array for frontend display.
     */
    public function toCompactArray(string $for = 'customer'): array
    {
        $data = [
            'id' => $this->id,
            'key' => $this->key,
            'required' => $this->required,
            'type' => $this->type,
            'config' => $this->config,
            'format' => $this->format,
            'materialized' => $this->materialized,
            'name' => $for === 'customer'
                    ? $this->customer_name ?? $this->name
                    : $this->name,
            'description' => $for === 'customer'
                    ? $this->customer_description
                    : $this->description,
        ];

        if (
            isset($this->attributes['value']) ||
            isset($this->original['pivot_value'])
        ) {
            $data['value'] = $this->value;
        }

        return $data;
    }

    /**
     * Get normalized array for search/autocomplete.
     */
    public function toNormalizedArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'model_type' => self::MODEL_TYPE,
        ];
    }

    /**
     * Searchable array for Scout.
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'customer_name' => $this->customer_name,
            'customer_description' => $this->customer_description,
            'created_at' => $this->created_at->timestamp ?? '_null',
            'updated_at' => $this->updated_at->timestamp ?? '_null',
        ];
    }

    /**
     * Get model type attribute.
     */
    public static function getModelTypeAttribute(): string
    {
        return self::MODEL_TYPE;
    }
}
