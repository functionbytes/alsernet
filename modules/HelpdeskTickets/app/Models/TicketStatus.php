<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Database\Factories\TicketStatusFactory;

class TicketStatus extends Model
{
    use HasFactory;

    protected static function newFactory(): TicketStatusFactory
    {
        return TicketStatusFactory::new();
    }

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_statuses';

    protected $fillable = [
        'name',
        'key',
        'slug',
        'color',
        'icon',
        'description',
        'order',
        'position',
        'is_default',
        'is_system',
        'is_open',
        'is_closed',
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

            // `slug` es NOT NULL/UNIQUE en la tabla pero ningún seeder ni
            // formulario lo rellena explícitamente — se deriva de 'key' (ya
            // suele venir en formato slug) o, si falta, del nombre.
            if (empty($status->slug)) {
                $status->slug = Str::slug($status->key ?: $status->name);
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

        static::saved(function () {
            Cache::forget('helpdesk:default-status');
            Cache::forget('helpdesk:open-status');
            Cache::forget('helpdesk:closed-status');
            Cache::forget('helpdesk:open-status-ids');
            Cache::forget('helpdesk:closed-status-ids');
        });

        static::deleted(function () {
            Cache::forget('helpdesk:default-status');
            Cache::forget('helpdesk:open-status');
            Cache::forget('helpdesk:closed-status');
            Cache::forget('helpdesk:open-status-ids');
            Cache::forget('helpdesk:closed-status-ids');
        });
    }

    /**
     * Get tickets with this status
     */
    public function tickets(): HasMany
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
