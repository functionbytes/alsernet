<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlaPolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_sla_policies';

    protected $fillable = [
        'uid',
        'name',
        'description',
        'priority_id',
        'category_id',
        'first_response_time_hours',
        'resolution_time_hours',
        'business_hours_only',
        'warning_threshold_percent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'business_hours_only' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getFirstResponseLabelAttribute(): ?string
    {
        return $this->formatHours($this->first_response_time_hours);
    }

    public function getResolutionLabelAttribute(): ?string
    {
        return $this->formatHours($this->resolution_time_hours);
    }

    public static function getDefaultPolicy(): ?self
    {
        return static::query()->active()->first();
    }

    private function formatHours(?int $hours): ?string
    {
        if ($hours === null) {
            return null;
        }

        if ($hours >= 24) {
            $days = intdiv($hours, 24);
            $remaining = $hours % 24;

            return $remaining > 0 ? "{$days}d {$remaining}h" : "{$days}d";
        }

        return "{$hours}h";
    }
}
