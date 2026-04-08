<?php

namespace Modules\Helpdesk\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlaPolicy extends Model
{
    use HasUid, SoftDeletes;

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
            'first_response_time_hours' => 'integer',
            'resolution_time_hours' => 'integer',
            'warning_threshold_percent' => 'integer',
        ];
    }

    /**
     * Priority for this SLA policy
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    /**
     * Category for this SLA policy
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Tickets using this SLA policy
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'sla_policy_id');
    }
}
