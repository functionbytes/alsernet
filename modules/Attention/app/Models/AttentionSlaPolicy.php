<?php

namespace Modules\Attention\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttentionSlaPolicy extends Model
{
    protected $table = 'attention_sla_policies';

    protected $fillable = [
        'name',
        'description',
        'response_time',
        'resolution_time',
        'closure_time',
        'business_hours_only',
        'business_hours',
        'timezone',
        'type_multipliers',
        'enable_escalation',
        'escalation_threshold_percent',
        'escalation_recipients',
        'active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'response_time' => 'integer',
            'resolution_time' => 'integer',
            'closure_time' => 'integer',
            'business_hours_only' => 'boolean',
            'business_hours' => 'array',
            'type_multipliers' => 'array',
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

    public function attentions(): HasMany
    {
        return $this->hasMany(Attention::class, 'sla_policy_id');
    }

    public function breaches(): HasMany
    {
        return $this->hasMany(AttentionSlaBreach::class, 'sla_policy_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public function getMultiplierForType(string $typeCode): float
    {
        $multipliers = $this->type_multipliers ?? [
            'PETICION' => 1.0,
            'QUEJA' => 0.7,
            'RECLAMO' => 0.7,
            'SUGERENCIA' => 1.3,
            'FELICITACION' => 1.5,
        ];

        return $multipliers[$typeCode] ?? 1.0;
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
