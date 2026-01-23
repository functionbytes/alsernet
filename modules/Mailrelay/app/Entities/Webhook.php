<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'method',
        'events',
        'is_active',
        'auth_type',
        'auth_config',
        'timeout',
        'max_retries',
        'verify_ssl',
        'secret',
        'last_triggered_at',
        'last_status_code',
        'last_response_time',
        'total_calls',
        'failed_calls',
    ];

    protected $casts = [
        'events' => 'array',
        'auth_config' => 'array',
        'is_active' => 'boolean',
        'verify_ssl' => 'boolean',
        'timeout' => 'integer',
        'max_retries' => 'integer',
        'last_status_code' => 'integer',
        'last_response_time' => 'integer',
        'total_calls' => 'integer',
        'failed_calls' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    protected $attributes = [
        'method' => 'POST',
        'is_active' => true,
        'auth_type' => 'none',
        'timeout' => 30,
        'max_retries' => 3,
        'verify_ssl' => true,
        'total_calls' => 0,
        'failed_calls' => 0,
    ];

    /**
     * Scope for active webhooks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for webhooks by event
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }

    /**
     * Check if webhook listens to specific event
     */
    public function listensTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    /**
     * Increment total calls counter
     */
    public function incrementCalls(): void
    {
        $this->increment('total_calls');
    }

    /**
     * Increment failed calls counter
     */
    public function incrementFailedCalls(): void
    {
        $this->increment('failed_calls');
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_calls === 0) {
            return 0;
        }

        $successCalls = $this->total_calls - $this->failed_calls;

        return round(($successCalls / $this->total_calls) * 100, 2);
    }
}
