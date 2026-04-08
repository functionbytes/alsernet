<?php

namespace Modules\Helpdesk\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Priority extends Model
{
    use HasUid, SoftDeletes;

    protected $table = 'helpdesk_priorities';

    protected $fillable = [
        'uid',
        'name',
        'slug',
        'level',
        'color',
        'response_time_hours',
        'resolution_time_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level' => 'integer',
            'response_time_hours' => 'integer',
            'resolution_time_hours' => 'integer',
        ];
    }

    /**
     * Tickets using this priority
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }

    /**
     * SLA policies using this priority
     */
    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class, 'priority_id');
    }
}
