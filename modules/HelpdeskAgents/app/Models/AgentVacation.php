<?php

namespace Modules\HelpdeskAgents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentVacation extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_agent_vacations';

    protected $fillable = [
        'user_id',
        'starts_at',
        'ends_at',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
