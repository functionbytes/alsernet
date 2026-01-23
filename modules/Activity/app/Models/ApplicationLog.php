<?php

namespace Modules\Activity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationLog extends Model
{
    use SoftDeletes;

    protected $table = 'application_logs';

    protected $fillable = [
        'level',
        'channel',
        'message',
        'context',
        'extra',
        'stack_trace',
        'user_id',
        'ip_address',
        'url',
        'method',
    ];

    protected $casts = [
        'context' => 'json',
        'extra' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Get logs by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope: Get logs by channel
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope: Get recent logs
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Get error logs only
     */
    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']);
    }

    /**
     * Scope: Get warning logs
     */
    public function scopeWarnings($query)
    {
        return $query->where('level', 'WARNING');
    }

    /**
     * Get formatted level with color
     */
    public function getLevelBadgeAttribute()
    {
        return match ($this->level) {
            'ERROR', 'CRITICAL' => '<span class="badge bg-danger">'.$this->level.'</span>',
            'WARNING' => '<span class="badge bg-warning">'.$this->level.'</span>',
            'INFO' => '<span class="badge bg-info">'.$this->level.'</span>',
            'DEBUG' => '<span class="badge bg-secondary">'.$this->level.'</span>',
            default => '<span class="badge bg-light text-dark">'.$this->level.'</span>',
        };
    }

    /**
     * Get truncated message
     */
    public function getTruncatedMessageAttribute($length = 100)
    {
        return substr($this->message, 0, $length).(strlen($this->message) > $length ? '...' : '');
    }
}
