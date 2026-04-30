<?php

namespace Modules\HelpdeskAgents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OncallRotation extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_oncall_rotations';

    protected $fillable = [
        'name',
        'user_ids',
        'shift_duration_hours',
        'started_at',
        'current_user_id',
        'next_handoff_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'user_ids' => 'array',
            'shift_duration_hours' => 'integer',
            'started_at' => 'datetime',
            'next_handoff_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function currentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_user_id');
    }
}
