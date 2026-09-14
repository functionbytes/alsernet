<?php

namespace Modules\HelpdeskSocial\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAgentWorkload extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_agent_workloads';

    protected $fillable = [
        'user_id',
        'active_assigned_count',
        'last_assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'active_assigned_count' => 'integer',
            'last_assigned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
