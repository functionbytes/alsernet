<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Automation extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_automations';

    protected $fillable = [
        'name',
        'description',
        'trigger_event',
        'conditions',
        'actions',
        'order',
        'is_active',
        'run_count',
        'last_run_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public static array $triggerEvents = [
        'ticket.created' => 'Ticket creado',
        'ticket.updated' => 'Ticket actualizado',
        'ticket.assigned' => 'Ticket asignado',
        'ticket.resolved' => 'Ticket resuelto',
        'ticket.closed' => 'Ticket cerrado',
    ];
}
