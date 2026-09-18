<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormConditionalEmail extends Model
{
    protected $table = 'form_conditional_emails';

    protected $fillable = [
        'form_id',
        'condition_field_key',
        'condition_operator',
        'condition_value',
        'admin_template_id',
        'client_template_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function matches(string $fieldValue): bool
    {
        $conditionValue = (string) $this->condition_value;

        return match ($this->condition_operator) {
            'equals' => $fieldValue === $conditionValue,
            'not_equals' => $fieldValue !== $conditionValue,
            'contains' => str_contains($fieldValue, $conditionValue),
            'starts_with' => str_starts_with($fieldValue, $conditionValue),
            'ends_with' => str_ends_with($fieldValue, $conditionValue),
            default => false,
        };
    }
}
