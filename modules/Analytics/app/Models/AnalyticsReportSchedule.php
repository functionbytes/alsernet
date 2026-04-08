<?php

namespace Modules\Analytics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AnalyticsReportSchedule extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'frequency',
        'email',
        'format',
        'metrics',
        'is_active',
        'last_sent_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeDue(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('next_run_at', '<=', Carbon::now());
    }
}
