<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

class StatusIncident extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_status_incidents';

    protected $fillable = [
        'title',
        'body',
        'severity',
        'status',
        'affected_components',
        'started_at',
        'resolved_at',
    ];

    const SEVERITIES = [
        'minor' => 'Menor',
        'major' => 'Mayor',
        'critical' => 'Critico',
    ];

    const STATUSES = [
        'investigating' => 'Investigando',
        'identified' => 'Identificado',
        'monitoring' => 'Monitoreando',
        'resolved' => 'Resuelto',
    ];

    const SEVERITY_COLORS = [
        'minor' => 'warning',
        'major' => 'danger',
        'critical' => 'danger',
    ];

    const STATUS_COLORS = [
        'investigating' => 'danger',
        'identified' => 'warning',
        'monitoring' => 'info',
        'resolved' => 'success',
    ];

    protected function casts(): array
    {
        return [
            'affected_components' => 'array',
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeRecent($query, int $days = 90)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    public function getIsResolvedAttribute(): bool
    {
        return $this->resolved_at !== null;
    }

    public function getSeverityLabelAttribute(): string
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSeverityColorAttribute(): string
    {
        return self::SEVERITY_COLORS[$this->severity] ?? 'secondary';
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }
}
