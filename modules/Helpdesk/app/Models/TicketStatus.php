<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TicketStatus extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_statuses';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'order',
        'is_default',
        'is_system',
        'is_open',
        'stops_sla_timer',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_system' => 'boolean',
            'is_open' => 'boolean',
            'stops_sla_timer' => 'boolean',
            'active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-increment order for new statuses
        static::creating(function ($status) {
            if (is_null($status->order)) {
                $maxOrder = static::max('order') ?? 0;
                $status->order = $maxOrder + 1;
            }

            // Ensure only one default status exists
            if ($status->is_default) {
                static::where('is_default', true)->update(['is_default' => false]);
            }
        });

        static::updating(function ($status) {
            // Ensure only one default status exists
            if ($status->is_default && $status->isDirty('is_default')) {
                static::where('id', '!=', $status->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get tickets with this status
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }

    /**
     * Scope to get only active statuses.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get only open statuses.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_open', true);
    }

    /**
     * Scope to get only closed statuses.
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('is_open', false);
    }

    /**
     * Scope to get statuses that stop SLA timer.
     */
    public function scopeStopsSla(Builder $query): Builder
    {
        return $query->where('stops_sla_timer', true);
    }

    /**
     * Scope to get statuses ordered by their sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Get the default status.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Check if this status can be deleted.
     */
    public function canDelete(): bool
    {
        return ! $this->is_system;
    }

    /**
     * Reorder statuses based on an array of IDs.
     */
    public static function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            static::where('id', $id)->update(['order' => $order + 1]);
        }
    }
}
