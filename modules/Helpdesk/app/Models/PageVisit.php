<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVisit extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_page_visits';

    // Columnas reales de helpdesk_page_visits (migración 2025_12_29_020925):
    // la fecha de la visita es created_at y el tiempo en página duration_seconds.
    protected $fillable = [
        'customer_id',
        'page_url',
        'referrer',
        'duration_seconds',
        'device_type',
        'browser',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the customer that made this visit
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Scope: Get visits from last N days
     */
    public function scopeLastDays($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Get visits with minimum time spent
     */
    public function scopeWithMinimumTimeSpent($query, $seconds = 5)
    {
        return $query->where('duration_seconds', '>=', $seconds);
    }

    /**
     * Scope: Get most visited pages
     */
    public function scopeMostVisited($query, $limit = 10)
    {
        return $query
            ->selectRaw('page_url, COUNT(*) as visit_count')
            ->groupBy('page_url')
            ->orderByDesc('visit_count')
            ->limit($limit);
    }

    /**
     * Get readable time spent
     */
    public function getReadableTimeSpentAttribute()
    {
        $seconds = (int) $this->duration_seconds;

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        if ($seconds < 3600) {
            $minutes = intdiv($seconds, 60);

            return "{$minutes}m";
        }

        $hours = intdiv($seconds, 3600);

        return "{$hours}h";
    }

    /**
     * Get domain from URL
     */
    public function getDomainAttribute()
    {
        $parsed = parse_url($this->page_url);

        return $parsed['host'] ?? 'unknown';
    }
}
