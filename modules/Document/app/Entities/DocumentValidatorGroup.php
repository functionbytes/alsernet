<?php

namespace Modules\Document\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Modules\Document\Traits\HasUid;
use Illuminate\Support\Facades\Cache;

class DocumentValidatorGroup extends Model
{
    use HasUid;

    protected $table = 'document_validator_groups';

    protected $fillable = [
        'name',
        'key',
        'description',
        'assignment_mode',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Auto-increment sort_order for new groups
        static::creating(function (self $group) {
            if (is_null($group->sort_order)) {
                $maxOrder = static::max('sort_order') ?? 0;
                $group->sort_order = $maxOrder + 1;
            }
        });

        // Ensure only one default group
        static::saving(function (self $group) {
            if ($group->is_default && $group->isDirty('is_default')) {
                static::where('id', '!=', $group->id ?? 0)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'document_validator_group_user',
            'validator_group_id',
            'user_id'
        )->withPivot('priority', 'created_at');
    }

    public function primaryUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('priority', 'primary');
    }

    public function backupUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('priority', 'backup');
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(DocumentValidatorGroupConfiguration::class, 'validator_group_id');
    }

    public function configurationHistory(): HasMany
    {
        return $this->hasMany(DocumentValidatorGroupConfigurationHistory::class, 'validator_group_id');
    }

    /**
     * Permissions assigned to this validator group
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            DocumentPermission::class,
            'document_validator_group_permission',
            'validator_group_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * Check if group has a specific permission
     */
    public function hasPermission(string $permissionName): bool
    {
        return Cache::remember(
            "group_{$this->id}_has_permission_{$permissionName}",
            3600,
            fn () => $this->permissions()
                ->where('name', $permissionName)
                ->where('is_active', true)
                ->exists()
        );
    }

    /**
     * Check if group can perform an action (alias for hasPermission)
     */
    public function can(string $action): bool
    {
        return $this->hasPermission($action);
    }

    /**
     * Give permission to group
     */
    public function givePermissionTo(string|DocumentPermission $permission): self
    {
        $permissionModel = is_string($permission)
            ? DocumentPermission::findByName($permission)
            : $permission;

        if ($permissionModel && ! $this->hasPermission($permissionModel->name)) {
            $this->permissions()->attach($permissionModel->id);
            Cache::forget("group_{$this->id}_has_permission_{$permissionModel->name}");
        }

        return $this;
    }

    /**
     * Revoke permission from group
     */
    public function revokePermissionFrom(string|DocumentPermission $permission): self
    {
        $permissionModel = is_string($permission)
            ? DocumentPermission::findByName($permission)
            : $permission;

        if ($permissionModel) {
            $this->permissions()->detach($permissionModel->id);
            Cache::forget("group_{$this->id}_has_permission_{$permissionModel->name}");
        }

        return $this;
    }

    /**
     * Sync permissions for this group
     */
    public function syncPermissions(array $permissions): self
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            if ($permission instanceof DocumentPermission) {
                return $permission->id;
            }

            return DocumentPermission::findByName($permission)?->id;
        })->filter()->toArray();

        $this->permissions()->sync($permissionIds);

        // Clear all permission cache for this group
        Cache::forget("group_{$this->id}_has_permission_*");

        return $this;
    }

    public static function findDefault(): ?self
    {
        return static::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public static function findByKey(string $key): ?self
    {
        return static::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
    }

    public static function getActiveOrdered(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public static function getByKeysInOrder(array $keys): Collection
    {
        $groups = static::query()
            ->whereIn('key', $keys)
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        // Return in the order of keys provided
        return collect($keys)
            ->map(fn (string $key) => $groups->get($key))
            ->filter();
    }

    public function getNextUser(?string $entityType = null): ?User
    {
        $users = $this->primaryUsers()->get();

        if ($users->isEmpty()) {
            $users = $this->backupUsers()->get();
        }

        if ($users->isEmpty()) {
            return null;
        }

        return match ($this->assignment_mode) {
            'round_robin' => $this->getNextUserRoundRobin($users),
            'load_balanced' => $this->getNextUserLoadBalanced($users, $entityType),
            default => $users->first(),
        };
    }

    protected function getNextUserRoundRobin(Collection $users): User
    {
        return $users->sortBy(function (User $user) {
            return $user->pivot->created_at ?? now();
        })->first();
    }

    protected function getNextUserLoadBalanced(Collection $users, ?string $entityType = null): User
    {
        if ($entityType && class_exists($entityType)) {
            return $users->sortBy(function (User $user) use ($entityType) {
                if (method_exists($entityType, 'countPendingForUser')) {
                    return $entityType::countPendingForUser($user->id);
                }

                return 0;
            })->first();
        }

        return $users->first();
    }

    public function hasUser(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->users()->where('users.id', $userId)->exists();
    }

    public function canUserValidate(User|int $user): bool
    {
        return $this->is_active && $this->hasUser($user);
    }

    public function scopeKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public static function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            static::where('id', $id)->update(['sort_order' => $order + 1]);
        }
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public static function getEmailConfigurationsForUser(User|int $user): array
    {
        $userId = $user instanceof User ? $user->id : $user;

        // Get all active groups the user belongs to
        $userGroups = static::query()
            ->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->where('is_active', true)
            ->with(['configurations' => function ($q) {
                $q->where('category', 'email_actions')
                    ->where('is_active', true);
            }])
            ->get();

        // If user doesn't belong to any group, return default (all disabled)
        if ($userGroups->isEmpty()) {
            return [
                'enable_initial_request' => false,
                'enable_missing_docs' => false,
                'enable_reminder' => false,
                'enable_upload_confirmation' => false,
                'enable_approval' => false,
                'enable_rejection' => false,
                'enable_custom_email' => false,
            ];
        }

        $mergedConfig = [];
        foreach ($userGroups as $group) {
            foreach ($group->configurations as $config) {
                // If any group enables an action, it's enabled
                if (! isset($mergedConfig[$config->key]) || ! $mergedConfig[$config->key]) {
                    $mergedConfig[$config->key] = (bool) $config->value;
                } else {
                    // Use OR logic: if either is true, keep true
                    $mergedConfig[$config->key] = $mergedConfig[$config->key] || (bool) $config->value;
                }
            }
        }

        // Ensure all expected keys exist with defaults
        return array_merge([
            'enable_initial_request' => false,
            'enable_missing_docs' => false,
            'enable_reminder' => false,
            'enable_upload_confirmation' => false,
            'enable_approval' => false,
            'enable_rejection' => false,
            'enable_custom_email' => false,
        ], $mergedConfig);
    }
}
