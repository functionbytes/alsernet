<?php

namespace Modules\Document\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Document\Entities\DocumentPermission;
use Modules\Document\Entities\DocumentValidatorGroup;

/**
 * Controller for managing permissions of Document Validator Groups
 * Provides CRUD operations for assigning permissions to groups
 */
class DocumentGroupPermissionsController extends Controller
{
    /**
     * Display the permissions management view for a specific group
     *
     * @param  DocumentValidatorGroup  $group
     * @return \Illuminate\View\View
     */
    public function edit(DocumentValidatorGroup $group)
    {
        // Get all available permissions grouped by category
        $permissionsByCategory = DocumentPermission::active()
            ->ordered()
            ->get()
            ->groupBy('category');

        // Get current permissions for this group
        $currentPermissions = $group->permissions()->pluck('document_permissions.id')->toArray();

        // Category helpers
        $categoryIcons = $this->getCategoryIcons();
        $categoryLabels = $this->getCategoryLabels();

        return view('documents::settings.groups.permissions', compact(
            'group',
            'permissionsByCategory',
            'currentPermissions',
            'categoryIcons',
            'categoryLabels'
        ));
    }

    /**
     * Get icons for each category
     */
    protected function getCategoryIcons(): array
    {
        return [
            'validation' => 'check-circle',
            'files' => 'file-alt',
            'emails' => 'envelope',
            'management' => 'cog',
            'sync' => 'sync',
            'advanced' => 'star',
            'default' => 'folder',
        ];
    }

    /**
     * Get labels for each category
     */
    protected function getCategoryLabels(): array
    {
        return [
            'validation' => 'Validación',
            'files' => 'Archivos',
            'emails' => 'Emails',
            'management' => 'Gestión',
            'sync' => 'Sincronización',
            'advanced' => 'Avanzado',
        ];
    }

    /**
     * Update permissions for a specific group
     *
     * @param  Request  $request
     * @param  DocumentValidatorGroup  $group
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, DocumentValidatorGroup $group)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:document_permissions,id',
        ]);

        // Sync permissions (this will remove old ones and add new ones)
        $permissionIds = $validated['permissions'] ?? [];
        $group->permissions()->sync($permissionIds);

        // Clear cache for all users in this group
        $this->clearGroupPermissionsCache($group);

        return redirect()
            ->route('settings.documents.groups.permissions.edit', $group)
            ->with('success', 'Permisos actualizados exitosamente.');
    }

    /**
     * Get permissions for a group (AJAX endpoint)
     *
     * @param  DocumentValidatorGroup  $group
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(DocumentValidatorGroup $group)
    {
        $permissions = $group->permissions()
            ->where('is_active', true)
            ->get()
            ->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => $permission->label,
                    'category' => $permission->category,
                    'description' => $permission->description,
                ];
            });

        return response()->json([
            'group' => [
                'id' => $group->id,
                'uid' => $group->uid,
                'name' => $group->name,
                'key' => $group->key,
            ],
            'permissions' => $permissions,
        ]);
    }

    /**
     * Get all available permissions (AJAX endpoint)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function available()
    {
        $permissions = DocumentPermission::active()
            ->ordered()
            ->get()
            ->groupBy('category')
            ->map(function ($categoryPermissions, $category) {
                return [
                    'category' => $category,
                    'label' => ucfirst($category),
                    'permissions' => $categoryPermissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'label' => $permission->label,
                            'description' => $permission->description,
                        ];
                    }),
                ];
            })
            ->values();

        return response()->json(['permissions' => $permissions]);
    }

    /**
     * Bulk update permissions for multiple groups
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'groups' => 'required|array',
            'groups.*.id' => 'required|exists:document_validator_groups,id',
            'groups.*.permissions' => 'nullable|array',
            'groups.*.permissions.*' => 'exists:document_permissions,id',
        ]);

        foreach ($validated['groups'] as $groupData) {
            $group = DocumentValidatorGroup::find($groupData['id']);
            $permissionIds = $groupData['permissions'] ?? [];
            $group->permissions()->sync($permissionIds);

            $this->clearGroupPermissionsCache($group);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permisos actualizados para '.count($validated['groups']).' grupos.',
        ]);
    }

    /**
     * Clear permissions cache for all users in a group
     *
     * @param  DocumentValidatorGroup  $group
     * @return void
     */
    protected function clearGroupPermissionsCache(DocumentValidatorGroup $group): void
    {
        // Clear cache for this specific group
        $permissionNames = DocumentPermission::pluck('name');

        foreach ($permissionNames as $permissionName) {
            Cache::forget("group_{$group->id}_has_permission_{$permissionName}");
        }
    }

    /**
     * Clone permissions from one group to another
     *
     * @param  Request  $request
     * @param  DocumentValidatorGroup  $sourceGroup
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clone(Request $request, DocumentValidatorGroup $sourceGroup)
    {
        $validated = $request->validate([
            'target_group_id' => 'required|exists:document_validator_groups,id',
        ]);

        $targetGroup = DocumentValidatorGroup::find($validated['target_group_id']);

        // Get all permissions from source group
        $permissionIds = $sourceGroup->permissions()->pluck('id')->toArray();

        // Sync to target group
        $targetGroup->permissions()->sync($permissionIds);

        $this->clearGroupPermissionsCache($targetGroup);

        return redirect()
            ->route('settings.documents.groups.permissions.edit', $targetGroup)
            ->with('success', "Permisos copiados desde '{$sourceGroup->name}' exitosamente.");
    }
}
