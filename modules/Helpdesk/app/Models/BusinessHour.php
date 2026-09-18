<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_business_hours';

    protected $fillable = [
        'day_of_week',
        'is_open',
        'opens_at',
        'closes_at',
        'timezone',
    ];

    public const DAY_NAMES = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'day_of_week' => 'integer',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_open', true);
    }

    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? 'Desconocido';
    }

    public static function initializeDefaults(): void
    {
        if (static::count() >= 7) {
            return;
        }

        $closedDays = [0, 6]; // Domingo y Sábado

        for ($day = 0; $day <= 6; $day++) {
            static::firstOrCreate(
                ['day_of_week' => $day],
                [
                    'is_open' => ! in_array($day, $closedDays),
                    'opens_at' => '09:00:00',
                    'closes_at' => '18:00:00',
                    'timezone' => 'America/Mexico_City',
                ]
            );
        }
    }
}
