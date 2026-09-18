<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Model;

class TicketDailyReport extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_daily_reports';

    protected $fillable = [
        'report_date',
        'total_created',
        'total_closed',
        'total_resolved',
        'avg_response_time_minutes',
        'avg_resolution_time_minutes',
        'sla_breached_count',
        'by_category',
        'by_priority',
        'by_status',
        'agent_performance',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'total_created' => 'integer',
            'total_closed' => 'integer',
            'total_resolved' => 'integer',
            'avg_response_time_minutes' => 'float',
            'avg_resolution_time_minutes' => 'float',
            'sla_breached_count' => 'integer',
            'by_category' => 'array',
            'by_priority' => 'array',
            'by_status' => 'array',
            'agent_performance' => 'array',
        ];
    }
}
