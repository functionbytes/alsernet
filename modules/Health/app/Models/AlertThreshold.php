<?php

namespace Modules\Health\Models;

use Illuminate\Database\Eloquent\Model;

class AlertThreshold extends Model
{
    protected $fillable = [
        'name',
        'type',
        'conditions',
        'channels',
        'recipients',
        'is_active',
        'last_triggered_at',
        'cooldown_minutes',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'channels' => 'array',
            'recipients' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function isInCooldown(): bool
    {
        return $this->last_triggered_at !== null
            && $this->last_triggered_at->diffInMinutes(now()) < $this->cooldown_minutes;
    }

    public function markTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }
}
