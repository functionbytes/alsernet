<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailrelay\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Display the permissions matrix.
     */
    public function index(): View
    {
        Gate::authorize('mailrelay.settings.permissions.view');

        $roles = Role::orderBy('name')->get();
        $permissions = Permission::where('name', 'like', 'mailrelay.%')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                $parts = explode('.', $permission->name);

                return $parts[1] ?? 'other';
            });

        return view('mailrelay::settings.permissions.index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Synchronize permissions to database.
     */
    public function sync(): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.permissions.manage');

        try {
            DB::beginTransaction();

            $permissionsToCreate = [
                // General Settings
                'mailrelay.settings.general.view',
                'mailrelay.settings.general.update',

                // API Settings
                'mailrelay.settings.api.view',
                'mailrelay.settings.api.update',
                'mailrelay.settings.api.test',

                // Templates
                'mailrelay.settings.templates.view',
                'mailrelay.settings.templates.create',
                'mailrelay.settings.templates.edit',
                'mailrelay.settings.templates.delete',

                // Groups
                'mailrelay.settings.groups.view',
                'mailrelay.settings.groups.create',
                'mailrelay.settings.groups.edit',
                'mailrelay.settings.groups.delete',

                // Custom Fields
                'mailrelay.settings.custom-fields.view',
                'mailrelay.settings.custom-fields.create',
                'mailrelay.settings.custom-fields.edit',
                'mailrelay.settings.custom-fields.delete',

                // Automations
                'mailrelay.settings.automations.view',
                'mailrelay.settings.automations.create',
                'mailrelay.settings.automations.edit',
                'mailrelay.settings.automations.delete',

                // Webhooks
                'mailrelay.settings.webhooks.view',
                'mailrelay.settings.webhooks.create',
                'mailrelay.settings.webhooks.edit',
                'mailrelay.settings.webhooks.delete',

                // Permissions Management
                'mailrelay.settings.permissions.view',
                'mailrelay.settings.permissions.manage',

                // Subscribers
                'mailrelay.subscribers.view',
                'mailrelay.subscribers.create',
                'mailrelay.subscribers.edit',
                'mailrelay.subscribers.delete',
                'mailrelay.subscribers.sync',
                'mailrelay.subscribers.export',
                'mailrelay.subscribers.import',

                // Campaigns
                'mailrelay.campaigns.view',
                'mailrelay.campaigns.create',
                'mailrelay.campaigns.edit',
                'mailrelay.campaigns.delete',
                'mailrelay.campaigns.send',

                // Analytics
                'mailrelay.analytics.view',
                'mailrelay.analytics.export',

                // Logs
                'mailrelay.logs.view',
                'mailrelay.logs.delete',
            ];

            $created = 0;
            $existing = 0;

            foreach ($permissionsToCreate as $permissionName) {
                $permission = Permission::firstOrCreate(
                    ['name' => $permissionName],
                    ['guard_name' => 'web']
                );

                if ($permission->wasRecentlyCreated) {
                    $created++;
                } else {
                    $existing++;
                }
            }

            DB::commit();

            return redirect()
                ->route('managers.settings.mailrelay.permissions.index')
                ->with('success', "Permisos sincronizados: {$created} creados, {$existing} existentes.");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Error al sincronizar permisos: '.$e->getMessage());
        }
    }

    /**
     * Assign permissions to roles.
     */
    public function assign(Request $request): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.permissions.manage');

        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'role_id' => 'required|exists:roles,id',
                'permissions' => 'array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            $role = Role::findOrFail($validated['role_id']);
            $permissions = Permission::whereIn('id', $validated['permissions'] ?? [])->get();

            // Only sync Mailrelay permissions
            $currentMailrelayPermissions = $role->permissions()
                ->where('name', 'like', 'mailrelay.%')
                ->pluck('id')
                ->toArray();

            $otherPermissions = $role->permissions()
                ->where('name', 'not like', 'mailrelay.%')
                ->pluck('id')
                ->toArray();

            $newPermissions = array_merge($otherPermissions, $permissions->pluck('id')->toArray());

            $role->syncPermissions($newPermissions);

            DB::commit();

            return redirect()
                ->route('managers.settings.mailrelay.permissions.index')
                ->with('success', 'Permisos asignados correctamente al rol.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Error al asignar permisos: '.$e->getMessage());
        }
    }
}
