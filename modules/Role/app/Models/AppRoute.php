<?php

namespace Modules\Role\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    public function scopeByProfile($query, $profile)
    {
        return $query->where('profile', $profile);
    }

    /**
     * Scope: Get active routes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get routes by method
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Get unique route profiles
     */
    public static function getProfiles()
    {
        return static::distinct()
            ->pluck('profile')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Generate hash for route comparison
     */
    public static function generateHash($routeName, $path, $method, $profile = null)
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
    public function permissions()
    {
        return $this->hasMany('Modules\Role\Models\RoutePermission', 'route_id');
    }

    /**
     * Check if route has a specific permission
     */
    public function hasPermission($permissionId)
    {
        return $this->permissions()
            ->where('permission_id', $permissionId)
            ->exists();
    }
}
