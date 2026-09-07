<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormAbandonTracking extends Model
{
    protected $table = 'form_abandon_tracking';

    protected $fillable = [
        'form_id',
        'session_token',
        'email',
        'partial_data',
        'current_step',
        'last_field_key',
        'started_at',
        'last_activity_at',
        'reminder_sent_at',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'partial_data' => 'array',
            'is_completed' => 'boolean',
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'current_step' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function scopeIncomplete($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeNeedsReminder($query, int $hoursAfter = 24)
    {
        return $query->incomplete()
            ->whereNull('reminder_sent_at')
            ->where('last_activity_at', '<=', now()->subHours($hoursAfter));
    }

    public function scopeForForm($query, int $formId)
    {
        return $query->where('form_id', $formId);
    }

    public function markCompleted(): void
    {
        $this->update(['is_completed' => true]);
    }

    public function updateActivity(array $partialData, string $lastFieldKey, int $currentStep): void
    {
        $this->update([
            'partial_data' => $partialData,
            'last_field_key' => $lastFieldKey,
            'current_step' => $currentStep,
            'last_activity_at' => now(),
        ]);
    }
}
