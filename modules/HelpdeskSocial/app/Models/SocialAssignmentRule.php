<?php

namespace Modules\HelpdeskSocial\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskSocial\Database\Factories\SocialAssignmentRuleFactory;

class SocialAssignmentRule extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_assignment_rules';

    protected $fillable = [
        'name',
        'conditions',
        'assignee_user_id',
        'assignment_strategy',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    protected static function newFactory(): SocialAssignmentRuleFactory
    {
        return SocialAssignmentRuleFactory::new();
    }
}
