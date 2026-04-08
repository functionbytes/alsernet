<?php

namespace Modules\Role\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppRoute extends Model
{
    use HasFactory;

    protected $table = 'app_routes';

    protected $fillable = [
        'name',
        'path',
        'method',
        'profile',
        'middleware',
        'controller',
        'action',
        'description',
        'requires_auth',
        'is_active',
        'hash',
    ];

    protected $casts = [
        'middleware' => 'array',
        'requires_auth' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Get routes by profile
     */
    public function scopeByProfile(Builder $query, string $profile): Builder
    {
        return $query->where('profile', $profile);
    }

    /**
     * Scope: Get active routes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get routes by method
     */
    public function scopeByMethod(Builder $query, string $method): Builder
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Get unique route profiles
     *
     * @return array<string>
     */
    public static function getProfiles(): array
    {
        return static::distinct()
            ->pluck('profile')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Generate hash for route comparison
     */
    public static function generateHash(string $routeName, string $path, string $method, ?string $profile = null): string
    {
        return md5(json_encode([
            'name' => $routeName,
            'path' => $path,
            'method' => strtoupper($method),
            'profile' => $profile,
        ]));
    }

    /**
     * Relationship: Route has many permissions
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(RoutePermission::class, 'route_id');
    }

    /**
     * Check if route has a specific permission
     */
    public function hasPermission(int $permissionId): bool
    {
        return $this->permissions()
            ->where('permission_id', $permissionId)
            ->exists();
    }
}
