<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Forms\Database\Factories\FormSubmissionValueFactory;

class FormSubmissionValue extends Model
{
    use HasFactory;

    protected $table = 'form_submission_values';

    protected static function newFactory(): Factory
    {
        return FormSubmissionValueFactory::new();
    }

    protected $fillable = [
        'submission_id',
        'form_field_id',
        'field_key',
        'field_label',
        'field_type',
        'value',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    public function getDisplayValue(): string
    {
        if (empty($this->value)) {
            return '';
        }

        if ($this->field_type === 'signature') {
            return '[Firma digital]';
        }

        $decoded = json_decode($this->value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return implode(', ', $decoded);
        }

        return $this->value;
    }
}
