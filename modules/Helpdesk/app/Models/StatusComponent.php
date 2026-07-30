<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;

class StatusComponent extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_status_components';

    protected $fillable = [
        'name',
        'description',
        'status',
        'order',
        'is_visible',
    ];

    const STATUSES = [
        'operational' => 'Operacional',
        'degraded' => 'Degradado',
        'partial_outage' => 'Interrupcion parcial',
        'major_outage' => 'Interrupcion mayor',
        'maintenance' => 'En mantenimiento',
    ];

    const STATUS_COLORS = [
        'operational' => 'success',
        'degraded' => 'warning',
        'partial_outage' => 'warning',
        'major_outage' => 'danger',
        'maintenance' => 'info',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true)->orderBy('order');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }
}
