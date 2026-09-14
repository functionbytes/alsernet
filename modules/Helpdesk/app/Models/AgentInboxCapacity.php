<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentInboxCapacity extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_agent_inbox_capacity';

    protected $fillable = [
        'user_id',
        'inbox_id',
        'max_concurrent',
        'accepts_new',
    ];

    protected function casts(): array
    {
        return [
            'max_concurrent' => 'integer',
            'accepts_new' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }
}
