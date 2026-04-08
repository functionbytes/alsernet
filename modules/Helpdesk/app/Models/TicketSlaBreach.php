<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSlaBreach extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_sla_breaches';

    protected $fillable = [
        'ticket_id',
        'breach_type',
        'due_at',
        'breached_at',
        'breach_duration_minutes',
        'resolved',
        'resolved_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'breached_at' => 'datetime',
            'breach_duration_minutes' => 'integer',
            'resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Set breached_at timestamp when creating
        static::creating(function ($breach) {
            if (! $breach->breached_at) {
                $breach->breached_at = now();
            }
        });
    }

    /**
     * Get the ticket that has this breach
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Scope to get only unresolved breaches
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope to get only resolved breaches
     */
    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    /**
     * Scope to get breaches by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('breach_type', $type);
    }

    /**
     * Mark breach as resolved
     */
    public function markAsResolved(): self
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Get readable breach type label
     */
    public function getBreachTypeLabelAttribute(): string
    {
        $labels = [
            'first_response' => 'Primera Respuesta',
            'next_response' => 'Siguiente Respuesta',
            'resolution' => 'Resolución',
        ];

        return $labels[$this->breach_type] ?? ucfirst(str_replace('_', ' ', $this->breach_type));
    }

    /**
     * Get formatted breach duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = $this->breach_duration_minutes;

        if ($minutes < 60) {
            return "{$minutes} minutos";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0
                ? "{$hours}h {$remainingMinutes}m"
                : "{$hours} horas";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0
            ? "{$days}d {$remainingHours}h"
            : "{$days} días";
    }

    /**
     * Check if breach is critical (>24 hours)
     */
    public function isCritical(): bool
    {
        return $this->breach_duration_minutes >= 1440; // 24 hours
    }
}
