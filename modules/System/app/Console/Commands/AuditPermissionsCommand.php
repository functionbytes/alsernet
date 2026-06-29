<?php

namespace Modules\System\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuditPermissionsCommand extends Command
{
    protected $signature = 'permissions:audit
                            {--module= : Filter by module name (partial match on first segment)}
                            {--missing : Show only permissions without any role assignment}';

    protected $description = 'Audit all permissions grouped by module, showing role assignments';

    public function handle(): int
    {
        $filter = $this->option('module');
        $missingOnly = $this->option('missing');

        $permissions = Permission::with('roles')->orderBy('name');

        if ($filter) {
            $permissions->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($filter).'%']);
        }

        if ($missingOnly) {
            $permissions->doesntHave('roles');
        }

        $permissions = $permissions->get();

        // Group by module: first segment before the first dot
        // Handles both "Backup.backups.index" → "Backup" and "blog.post.view" → "blog"
        $grouped = $permissions->groupBy(fn ($p) => $this->moduleSegment($p->name));

        $this->newLine();
        $this->line('<fg=cyan>Permission Audit Report</>');
        $this->line(str_repeat('─', 80));

        $totalPermissions = 0;
        $orphaned = 0;

        foreach ($grouped->sortKeys() as $module => $modulePermissions) {
            $this->newLine();
            $this->line("<fg=yellow>  {$module}</> ({$modulePermissions->count()} permissions)");

            $rows = [];

            foreach ($modulePermissions as $permission) {
                $roles = $permission->roles->pluck('name')->join(', ');
                $rolesDisplay = $roles ?: '<fg=red>No roles assigned</>';

                if (! $roles) {
                    $orphaned++;
                }

                $rows[] = ['  '.$permission->name, $rolesDisplay];
                $totalPermissions++;
            }

            $this->table(['Permission', 'Assigned to roles'], $rows);
        }

        $this->newLine();
        $this->line(str_repeat('─', 80));
        $this->line("Total permissions: <fg=green>{$totalPermissions}</>");

        $orphanColor = $orphaned > 0 ? 'red' : 'green';
        $this->line("Unassigned (orphaned): <fg={$orphanColor}>{$orphaned}</>");

        $this->newLine();
        $this->line('<fg=cyan>Roles Summary</>');

        $roleRows = Role::with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => [$r->name, $r->permissions->count()])
            ->toArray();

        $this->table(['Role', 'Permission count'], $roleRows);

        return self::SUCCESS;
    }

    /**
     * Extract the module segment from a permission name.
     * Works for both "Module.resource.action" and "module.resource.action" formats.
     */
    private function moduleSegment(string $permissionName): string
    {
        return explode('.', $permissionName)[0] ?? 'Other';
    }
}
