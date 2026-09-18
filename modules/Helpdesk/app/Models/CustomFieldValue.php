<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldValue extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_custom_field_values';

    protected $fillable = [
        'field_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'field_id');
    }

    /**
     * Get the typed value based on the field's type.
     */
    public function getTypedValue(): mixed
    {
        $type = $this->field?->type;

        return match ($type) {
            'number' => is_numeric($this->value) ? (float) $this->value : null,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'multi-select' => json_decode($this->value ?? '[]', true),
            default => $this->value,
        };
    }
}
