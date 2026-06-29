<?php

namespace Modules\Attention\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Festivos colombianos
 * Incluye festivos fijos, móviles y aquellos que se trasladan al lunes según Ley Emiliani
 */
class ColombianHoliday extends Model
{
    protected $table = 'colombian_holidays';

    protected $fillable = [
        'date',
        'name',
        'type',
        'is_monday_law',
    ];

    protected $casts = [
        'date' => 'date',
        'is_monday_law' => 'boolean',
    ];

    /**
     * Obtener festivos de un año específico
     */
    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year)->orderBy('date');
    }

    /**
     * Verificar si una fecha es festivo
     */
    public static function isHoliday(Carbon $date): bool
    {
        return self::where('date', $date->format('Y-m-d'))->exists();
    }

    /**
     * Obtener el festivo de una fecha específica
     */
    public static function getHoliday(Carbon $date): ?self
    {
        return self::where('date', $date->format('Y-m-d'))->first();
    }

    /**
     * Obtener todos los festivos de un año
     */
    public static function getHolidaysForYear(int $year): array
    {
        return self::forYear($year)->pluck('date')->map(function ($date) {
            return Carbon::parse($date);
        })->toArray();
    }

    /**
     * Verificar si un año tiene festivos registrados
     */
    public static function hasHolidaysForYear(int $year): bool
    {
        return self::whereYear('date', $year)->exists();
    }
}
