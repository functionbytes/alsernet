<?php

namespace Modules\Attention\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttentionSlaBreach extends Model
{
    protected $table = 'attention_sla_breaches';

    protected $fillable = [
        'attention_id',
        'sla_policy_id',
        'breach_type',
        'minutes_over',
        'escalated',
        'escalated_at',
        'resolved',
        'resolved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'escalated' => 'boolean',
            'escalated_at' => 'datetime',
            'resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'minutes_over' => 'integer',
        ];
    }

    public function attention(): BelongsTo
    {
        return $this->belongsTo(Attention::class);
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(AttentionSlaPolicy::class);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeByBreachType($query, string $type)
    {
        return $query->where('breach_type', $type);
    }

    public function scopeEscalated($query)
    {
        return $query->where('escalated', true);
    }

    public function resolve(?string $notes = null): void
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);
    }

    public function escalate(): void
    {
        if (! $this->escalated) {
            $this->update([
                'escalated' => true,
                'escalated_at' => now(),
            ]);
        }
    }
}
