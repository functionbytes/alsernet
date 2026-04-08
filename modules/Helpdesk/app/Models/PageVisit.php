<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_page_visits';

    protected $fillable = [
        'customer_id',
        'session_id',
        'page_url',
        'page_title',
        'referrer',
        'time_spent_seconds',
        'scroll_depth',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the customer that made this visit
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the session associated with this visit
     */
    public function session()
    {
        return $this->belongsTo(CustomerSession::class, 'session_id');
    }

    /**
     * Scope: Get visits from last N days
     */
    public function scopeLastDays($query, $days = 7)
    {
        return $query->where('visited_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Get visits with minimum time spent
     */
    public function scopeWithMinimumTimeSpent($query, $seconds = 5)
    {
        return $query->where('time_spent_seconds', '>=', $seconds);
    }

    /**
     * Scope: Get most visited pages
     */
    public function scopeMostVisited($query, $limit = 10)
    {
        return $query
            ->selectRaw('page_url, page_title, COUNT(*) as visit_count')
            ->groupBy('page_url', 'page_title')
            ->orderByDesc('visit_count')
            ->limit($limit);
    }

    /**
     * Get readable time spent
     */
    public function getReadableTimeSpentAttribute()
    {
        $seconds = $this->time_spent_seconds;

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
