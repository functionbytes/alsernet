<?php

namespace Modules\HelpdeskSla\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;

class Holiday extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_holidays';

    protected $fillable = [
        'date',
        'name',
        'is_recurring',
    ];

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget(BusinessHoursCalculator::HOLIDAYS_CACHE_KEY);

        static::saved($forget);
        static::deleted($forget);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }
}
