<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSlaPolicy extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_sla_policies';

    protected $fillable = [
        'name',
        'description',
        'first_response_time',
        'next_response_time',
        'resolution_time',
        'business_hours_only',
        'business_hours',
        'timezone',
        'priority_multipliers',
        'enable_escalation',
        'escalation_threshold_percent',
        'escalation_recipients',
        'active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'first_response_time' => 'integer',
            'next_response_time' => 'integer',
            'resolution_time' => 'integer',
            'business_hours_only' => 'boolean',
            'business_hours' => 'array',
            'priority_multipliers' => 'array',
            'enable_escalation' => 'boolean',
            'escalation_threshold_percent' => 'integer',
            'escalation_recipients' => 'array',
            'active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Ensure only one default policy exists
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

    /**
     * Get tickets using this SLA policy
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'sla_policy_id');
    }

    /**
     * Get categories using this as default SLA
     */
    public function categoriesUsingAsDefault()
    {
        return $this->hasMany(TicketCategory::class, 'default_sla_policy_id');
    }

    /**
     * Scope to get only active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get the default SLA policy.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get priority multiplier for a given priority
     */
    public function getMultiplierForPriority(string $priority): float
    {
        $multipliers = $this->priority_multipliers ?? [
            'urgent' => 0.25,
            'high' => 0.5,
            'normal' => 1.0,
            'low' => 2.0,
        ];

        return $multipliers[$priority] ?? 1.0;
    }

    /**
     * Get escalation threshold in minutes for a given time
     */
    public function getEscalationThreshold(int $totalMinutes): int
    {
        if (! $this->enable_escalation || ! $this->escalation_threshold_percent) {
            return $totalMinutes;
        }

        return (int) ($totalMinutes * ($this->escalation_threshold_percent / 100));
    }

    /**
     * Check if business hours are configured
     */
    public function hasBusinessHours(): bool
    {
        return $this->business_hours_only && ! empty($this->business_hours);
    }

    /**
     * Get formatted business hours for display
     */
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
