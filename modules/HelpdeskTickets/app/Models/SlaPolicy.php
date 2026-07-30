<?php

namespace Modules\HelpdeskTickets\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @deprecated Use Modules\Helpdesk\Models\TicketSlaPolicy instead.
 *     This model targets the legacy `helpdesk_sla_policies` table which
 *     is superseded by `helpdesk_ticket_sla_policies`. New code must use
 *     TicketSlaPolicy. Drop plan: ADR pending.
 */
class SlaPolicy extends Model
{
    use HasUid, SoftDeletes;

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
