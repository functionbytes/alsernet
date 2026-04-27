<?php

namespace Modules\Locations\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class LocationsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'locations.countries.view',
            'locations.countries.create',
            'locations.countries.update',
            'locations.countries.delete',
            'locations.states.view',
            'locations.states.create',
            'locations.states.update',
            'locations.states.delete',
            'locations.cities.view',
            'locations.cities.create',
            'locations.cities.update',
            'locations.cities.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
