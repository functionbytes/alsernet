<?php

namespace Modules\HelpdeskCompliance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Customer;

class ComplianceRequest extends Model
{
    public const TYPE_EXPORT = 'export';

    public const TYPE_DELETE_SOFT = 'delete_soft';

    public const TYPE_DELETE_HARD = 'delete_hard';

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_compliance_requests';

    protected $fillable = [
        'customer_id',
        'type',
        'status',
        'requested_by',
        'modules_affected',
        'result_summary',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'modules_affected' => 'array',
            'result_summary' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
