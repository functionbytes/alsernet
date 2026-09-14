<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentSlaPolicy extends Model
{
    protected $table = 'document_sla_policies';

    protected $fillable = [
        'name',
        'description',
        'upload_request_time',
        'review_time',
        'approval_time',
        'business_hours_only',
        'business_hours',
        'timezone',
        'document_type_multipliers',
        'enable_escalation',
        'escalation_threshold_percent',
        'escalation_recipients',
        'active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'upload_request_time' => 'integer',
            'review_time' => 'integer',
            'approval_time' => 'integer',
            'business_hours_only' => 'boolean',
            'business_hours' => 'array',
            'document_type_multipliers' => 'array',
            'enable_escalation' => 'boolean',
            'escalation_threshold_percent' => 'integer',
            'escalation_recipients' => 'array',
            'active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($policy) {
            if ($policy->is_default) {
                static::where('is_default', true)->update(['is_default' => false]);
            }
        });

        static::updating(function ($policy) {
            if ($policy->is_default && $policy->isDirty('is_default')) {
                static::where('id', '!=', $policy->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'sla_policy_id');
    }

    public function breaches(): HasMany
    {
        return $this->hasMany(DocumentSlaBreach::class, 'sla_policy_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public function getMultiplierForDocumentType(string $type): float
    {
        $multipliers = $this->document_type_multipliers ?? [
            'corta' => 0.75,
            'rifle' => 1.0,
            'escopeta' => 1.0,
            'dni' => 0.5,
            'general' => 1.0,
            'order' => 1.5,
        ];

        return $multipliers[$type] ?? 1.0;
    }

    public function getEscalationThreshold(int $totalMinutes): int
    {
        if (! $this->enable_escalation || ! $this->escalation_threshold_percent) {
            return $totalMinutes;
        }

        return (int) ($totalMinutes * ($this->escalation_threshold_percent / 100));
    }

    public function hasBusinessHours(): bool
    {
        return $this->business_hours_only && ! empty($this->business_hours);
    }

    public function getFormattedBusinessHours(): array
    {
        if (! $this->hasBusinessHours()) {
            return [];
        }

        return collect($this->business_hours)
            ->map(function ($hours, $day) {
                return [
                    'day' => ucfirst($day),
                    'start' => $hours['start'] ?? '09:00',
                    'end' => $hours['end'] ?? '17:00',
                ];
            })
            ->values()
            ->toArray();
    }
}
