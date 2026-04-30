<?php

namespace Modules\Chat\Models\Teams;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Chat\Database\Factories\TeamFactory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Concerns\BelongsToAccount;
use Modules\Chat\Models\Conversations\Conversation;

class Team extends Model
{
    use BelongsToAccount, HasFactory, SoftDeletes;

    protected $table = 'chat_teams';

    protected static function newFactory()
    {
        return TeamFactory::new();
    }

    protected $fillable = [
        'name',
        'description',
        'allow_auto_assign',
        'account_id',
    ];

    protected function casts(): array
    {
        return [
            'allow_auto_assign' => 'boolean',
        ];
    }

    /**
     * Get the account that owns the team
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the team members (users)
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_team_user')
            ->withPivot('is_lead', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get team leads.
     */
    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_team_user')
            ->wherePivot('is_lead', true)
            ->withPivot('is_lead', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get available members (online or busy).
     */
    public function availableMembers(): BelongsToMany
    {
        return $this->members()
            ->whereIn('availability_status', ['online', 'busy']);
    }

    /**
     * Get conversation assigned to this team
     */
    public function conversations(): HasMany
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
