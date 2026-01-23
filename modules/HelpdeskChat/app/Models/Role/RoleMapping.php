<?php

namespace Modules\HelpdeskChat\Models\Role;

use Illuminate\Database\Eloquent\Model;

class RoleMapping extends Model
{
    protected $table = 'role_mappings';

    protected $fillable = [
        'profile',
        'roles',
        'description',
        'is_active',
    ];

    protected $casts = [
        'roles' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all active role mappings
     */
    public static function getActive()
    {
        return self::where('is_active', true)->pluck('roles', 'profile')->toArray();
    }

    /**
     * Get mapping for specific profile
     */
    public static function getProfile($profile)
    {
        $mapping = self::where('profile', $profile)
            ->where('is_active', true)
            ->first();

        return $mapping?->roles ?? [];
    }

    /**
     * Seed default mappings
     */
    public static function seedDefaults()
    {
        $defaults = [
            [
                'profile' => 'manager',
                'roles' => ['super-admins', 'admins', 'theme'],
                'description' => 'Acceso a módulo manager con permisos de gerente general',
                'is_active' => true,
            ],
            [
                'profile' => 'callcenter',
                'roles' => ['super-admins', 'admins', 'callcenters'],
                'description' => 'Acceso a módulo call center para gestión y operaciones',
                'is_active' => true,
            ],
            [
                'profile' => 'inventory',
                'roles' => ['super-admins', 'admins', 'warehouses'],
                'description' => 'Acceso a módulo de inventario para gestión de stock',
                'is_active' => true,
            ],
            [
                'profile' => 'warehouse',
                'roles' => ['super-admins', 'admins', 'warehouses'],
                'description' => 'Acceso a módulo de almacén para gestión de inventario',
                'is_active' => true,
            ],
            [
                'profile' => 'shop',
                'roles' => ['super-admins', 'admins', 'shops'],
                'description' => 'Acceso a módulo de tienda para ventas y operaciones',
                'is_active' => true,
            ],
            [
                'profile' => 'administrative',
                'roles' => ['super-admins', 'admins', 'administratives'],
                'description' => 'Acceso a módulo administrativo para tareas administrativas',
                'is_active' => true,
            ],
        ];

        foreach ($defaults as $mapping) {
            self::updateOrCreate(
                ['profile' => $mapping['profile']],
                $mapping
            );
        }
    }
}
