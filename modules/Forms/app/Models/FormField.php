<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Forms\Database\Factories\FormFieldFactory;

class FormField extends Model
{
    use HasFactory;

    public const TYPES_WITH_OPTIONS = ['select', 'radio', 'checkbox', 'image_choice'];

    public const LAYOUT_TYPES = ['section_header', 'html_block', 'divider', 'spacer'];

    protected $table = 'form_fields';

    protected static function newFactory(): Factory
    {
        return FormFieldFactory::new();
    }

    protected $fillable = [
        'form_id',
        'type',
        'key',
        'name',
        'label',
        'placeholder',
        'help_text',
        'default_value',
        'options',
        'validation_rules',
        'conditions',
        'logic_jumps',
        'likert_rows',
        'is_required',
        'is_visible',
        'show_char_counter',
        'sort_order',
        'step_number',
        'min_value',
        'max_value',
        'step_value',
        'css_class',
        'width',
        'label_position',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'validation_rules' => 'array',
            'conditions' => 'array',
            'logic_jumps' => 'array',
            'likert_rows' => 'array',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'show_char_counter' => 'boolean',
            'sort_order' => 'integer',
            'step_number' => 'integer',
            'min_value' => 'float',
            'max_value' => 'float',
            'step_value' => 'float',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function submissionValues(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class, 'form_field_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeForStep($query, int $step)
    {
        return $query->where('step_number', $step);
    }

    public function scopeOfType($query, string|array $type)
    {
        return $query->whereIn('type', (array) $type);
    }

    public function isLayoutField(): bool
    {
        return in_array($this->type, self::LAYOUT_TYPES, true);
    }

    public function hasNoValue(): bool
    {
        return $this->isLayoutField() || $this->type === 'hidden';
    }
}
