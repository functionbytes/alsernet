<?php

namespace Modules\Chat\Models\Teams;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Chat\Models\Accounts\Account;

class TeamRole extends Model
{
    protected $table = 'chat_team_roles';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'permissions',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Role belongs to an account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if role has specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if role has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return ! empty(array_intersect($permissions, $this->permissions ?? []));
    }

    /**
     * Check if role has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return empty(array_diff($permissions, $this->permissions ?? []));
    }

    /**
     * Scope to filter roles by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to get default roles.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get available permissions list.
     */
    public static function getAvailablePermissions(): array
    {
        return [
            'conversation.view' => 'View conversation',
            'conversation.manage' => 'Manage conversation',
            'conversation.assign' => 'Assign conversation',
            'conversation.delete' => 'Delete conversation',

            'contacts.view' => 'View contacts',
            'contacts.manage' => 'Manage contacts',
            'contacts.delete' => 'Delete contacts',
            'contacts.export' => 'Export contacts',

            'teams.view' => 'View teams',
            'teams.manage' => 'Manage teams',

            'labels.view' => 'View labels',
            'labels.manage' => 'Manage labels',

            'canned_responses.view' => 'View canned responses',
            'canned_responses.manage' => 'Manage canned responses',

            'automation.view' => 'View automation rules',
            'automation.manage' => 'Manage automation rules',

            'reports.view' => 'View reports',
            'reports.export' => 'Export reports',

            'settings.view' => 'View settings',
            'settings.manage' => 'Manage settings',

            'inboxes.view' => 'View inboxes',
            'inboxes.manage' => 'Manage inboxes',

            'settings.full_access' => 'Full administrator access',
        ];
    }
}
