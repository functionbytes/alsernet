<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormFollowUp extends Model
{
    protected $table = 'form_follow_ups';

    protected $fillable = [
        'form_id',
        'name',
        'send_after_days',
        'email_template_id',
        'recipient_type',
        'condition_field_key',
        'condition_value',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'send_after_days' => 'integer',
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
}
