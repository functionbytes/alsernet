<?php

namespace Modules\HelpdeskChat\Models\Teams;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class Team extends Model
{
    protected $table = 'helpdesk_teams';

    protected $fillable = [
        'name',
        'description',
        'allow_auto_assign',
        'account_id',
    ];

    protected $casts = [
        'allow_auto_assign' => 'boolean',
    ];

    /**
     * Get the account that owns the team
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the team members (users)
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'helpdesk_team_user')
            ->withPivot('is_lead', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get team leads.
     */
    public function leads()
    {
        return $this->belongsToMany(User::class, 'helpdesk_team_user')
            ->wherePivot('is_lead', true)
            ->withPivot('is_lead', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get available members (online or busy).
     */
    public function availableMembers()
    {
        return $this->members()
            ->whereIn('availability_status', ['online', 'busy']);
    }

    /**
     * Get conversation assigned to this team
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Check if user is a member of this team.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user is a lead of this team.
     */
    public function isLead(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->wherePivot('is_lead', true)
            ->exists();
    }
}
