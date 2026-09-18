<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_custom_fields';

    protected $fillable = [
        'entity_type',
        'key',
        'label',
        'type',
        'options',
        'is_required',
        'default_value',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public const ENTITY_CUSTOMER = 'customer';

    public const ENTITY_CONVERSATION = 'conversation';

    public const TYPES = [
        'text' => 'Texto',
        'number' => 'Numero',
        'date' => 'Fecha',
        'boolean' => 'Si/No',
        'select' => 'Lista',
        'multi-select' => 'Lista multiple',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'field_id');
    }

    public function scopeForEntity(Builder $query, string $entityType): Builder
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('label');
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, ['select', 'multi-select'], true);
    }
}
