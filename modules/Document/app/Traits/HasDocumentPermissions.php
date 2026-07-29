<?php

namespace Modules\Document\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Document\Entities\DocumentValidatorGroup;

/**
 * Trait HasDocumentPermissions
 *
 * Este trait agrega funcionalidad de permisos del módulo Document al modelo User
 * Sigue el patrón de Spatie Permission pero scoped al módulo Document
 */
trait HasDocumentPermissions
{
    /**
     * Document Validator Groups that the user belongs to
     */
    public function documentValidatorGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            DocumentValidatorGroup::class,
            'document_validator_group_user',
            'user_id',
            'validator_group_id'
        )->withPivot('priority', 'created_at');
    }

    /**
     * Check if user has a permission within a specific document validator group
     *
     * @param  string  $permissionName  The permission to check (e.g., 'approve-documents')
     * @param  DocumentValidatorGroup|string  $group  The group model or key
     */
    public function hasPermissionInGroup(string $permissionName, $group): bool
    {
        $groupModel = $group instanceof DocumentValidatorGroup
            ? $group
            : DocumentValidatorGroup::findByKey($group);

        if (! $groupModel) {
            return false;
        }

        // Check if user belongs to the group
        if (! $groupModel->hasUser($this)) {
            return false;
        }

        // Check if the group has the permission
        return $groupModel->hasPermission($permissionName);
    }

    /**
     * Check if user can perform an action in any of their document groups
     *
     * @param  string  $action  The action/permission to check
     */
    public function canInDocumentModule(string $action): bool
    {
        return $this->documentValidatorGroups()
            ->where('is_active', true)
            ->get()
            ->contains(function ($group) use ($action) {
                return $group->can($action);
            });
    }

    /**
     * Get all permissions the user has across all their document validator groups
     *
     * @return array Array of permissions with their details and associated groups
     */
    public function getDocumentGroupPermissions(): array
    {
        $permissions = [];

        foreach ($this->documentValidatorGroups()->where('is_active', true)->get() as $group) {
            foreach ($group->permissions()->where('is_active', true)->get() as $permission) {
                if (! isset($permissions[$permission->name])) {
                    $permissions[$permission->name] = [
                        'name' => $permission->name,
                        'label' => $permission->label,
                        'category' => $permission->category,
                        'groups' => [],
                    ];
                }
                $permissions[$permission->name]['groups'][] = $group->name;
            }
        }

        return array_values($permissions);
    }

    /**
     * Check if user belongs to any document validator group
     */
    public function belongsToAnyDocumentGroup(): bool
    {
        return $this->documentValidatorGroups()->where('is_active', true)->exists();
    }

    /**
     * Get the user's primary document validator groups
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function primaryDocumentGroups()
    {
        return $this->documentValidatorGroups()
            ->where('is_active', true)
            ->wherePivot('priority', 'primary')
            ->get();
    }

    /**
     * Get the user's backup document validator groups
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function backupDocumentGroups()
    {
        return $this->documentValidatorGroups()
            ->where('is_active', true)
            ->wherePivot('priority', 'backup')
            ->get();
    }

    /**
     * Check if user has permission in any of their groups
     * Useful for authorization in controllers
     *
     * @param  string  $permission  The permission name
     */
    public function hasDocumentPermission(string $permission): bool
    {
        return $this->canInDocumentModule($permission);
    }

    // =========================================================================
    // UNIFIED PERMISSION CHECKER - Dynamic from database
    // =========================================================================

    /**
     * Unified permission checker - accepts any permission or component name
     * Dynamically resolves from database instead of hardcoded logic
     *
     * Usage:
     * - auth()->user()->canDocument('send-initial-request')  // Direct permission
     * - auth()->user()->canDocument('email-actions')         // Component with view- prefix
     * - auth()->user()->canDocument('send-reminder')         // Action permission
     *
     * @param  string  $name  Permission name or component name
     */
    public function canDocument(string $name): bool
    {
        // First, try direct permission check
        if ($this->canInDocumentModule($name)) {
            return true;
        }

        // Second, try with 'view-' prefix for component visibility (but avoid double-prefixing)
        if (! str_starts_with($name, 'view-')) {
            $viewPermission = 'view-'.$name;
            if ($this->canInDocumentModule($viewPermission)) {
                return true;
            }
        }

        // Third, dynamically check component mappings from database
        // Strip 'view-' prefix if present to match component names in mappings
        $componentName = str_starts_with($name, 'view-') ? substr($name, 5) : $name;

        return $this->resolveComponentPermissions($componentName);
    }

    /**
     * Resolve component name to required permissions
     * Dynamically resolves required permissions from database
     *
     * @param  string  $componentName  Name of the component (e.g., 'email-actions', 'document-management')
     */
    private function resolveComponentPermissions(string $componentName): bool
    {
        // Dynamic component-to-permissions mapping
        // Maps component names to their required permissions in the database
        $componentMappings = [
            // Email and notification actions
            'email-actions' => ['send-initial-request', 'send-reminders', 'send-missing-docs', 'send-approval', 'send-rejection', 'send-custom-email'],

            // Document management (edit, change status, etc.)
            'document-management' => ['edit-documents', 'create-documents'],

            // File operations
            'document-upload' => ['upload-documents'],
            'additional-attachments' => ['upload-attachments', 'delete-attachments'],

            // Workflow validation
            'validation-workflow' => ['approve-documents', 'reject-documents'],

            // Document notes
            'document-notes' => ['add-notes', 'edit-notes', 'delete-notes'],

            // View-only components - require SPECIFIC view permissions, not generic ones
            'action-history' => ['view-action-history'],
            'email-history' => ['view-email-history'],
            'status-timeline' => ['view-status-timeline'],
            'products-list' => ['view-products-list'],
            'order-details' => ['view-order-details'],
            'customer-information' => ['view-customer-information'],

            // Sync operations
            'sync-operations' => ['import-documents', 'sync-erp', 'sync-api'],
        ];

        // Get required permissions for this component
        $requiredPermissions = $componentMappings[$componentName] ?? [];

        if (empty($requiredPermissions)) {
            return false;
        }

        // Check if user has ANY of the required permissions
        foreach ($requiredPermissions as $permission) {
            if ($this->canInDocumentModule($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can perform an action on a specific document component
     * Legacy method - kept for backward compatibility, use canDocument() instead
     *
     * @param  string  $componentName  The component name
     * @param  string  $action  The action (view, edit, delete, etc.)
     */
    public function canActionDocumentComponent(string $componentName, string $action = 'view'): bool
    {
        // Dynamic action-to-permission mapping
        $actionMappings = [
            'email-actions' => [
                'send-initial-request' => 'send-initial-request',
                'send-reminder' => 'send-reminders',
                'send-missing-docs' => 'send-missing-docs',
                'send-approval' => 'send-approval',
                'send-rejection' => 'send-rejection',
                'send-custom' => 'send-custom-email',
            ],
            'document-management' => [
                'edit' => 'edit-documents',
                'create' => 'create-documents',
                'delete' => 'delete-documents',
            ],
            'document-upload' => [
                'upload' => 'upload-documents',
                'delete' => 'delete-documents',
            ],
            'validation-workflow' => [
                'approve' => 'approve-documents',
                'reject' => 'reject-documents',
            ],
            'document-notes' => [
                'add' => 'add-notes',
                'edit' => 'edit-notes',
                'delete' => 'delete-notes',
            ],
            'additional-attachments' => [
                'upload' => 'upload-attachments',
                'delete' => 'delete-attachments',
            ],
            'import-documents' => [
                'upload' => 'import-documents',
            ],
        ];

        // Get the required permission for this action
        $requiredPermission = $actionMappings[$componentName][$action] ?? null;

        if (! $requiredPermission) {
            return false;
        }

        return $this->canInDocumentModule($requiredPermission);
    }

    /**
     * Check if user can edit a specific document field
     * Allows granular control over which fields each user can edit
     *
     * @param  string  $fieldName  The field name (status, source, load, sync, upload, requires_financing)
     */
    public function canEditDocumentField(string $fieldName): bool
    {
        // Field-level permissions mapping
        $fieldPermissions = [
            'status' => 'edit-status',
            'source' => 'edit-source',
            'load' => 'edit-load',
            'sync' => 'edit-sync',
            'upload' => 'edit-upload',
            'requires_financing' => 'edit-financing',
        ];

        $requiredPermission = $fieldPermissions[$fieldName] ?? null;

        if (! $requiredPermission) {
            // Default to generic edit-documents permission if field not mapped
            return $this->canInDocumentModule('edit-documents');
        }

        return $this->canInDocumentModule($requiredPermission);
    }

    /**
     * Alias for backward compatibility - use canDocument() instead
     */
    public function canViewDocumentComponent(string $componentName): bool
    {
        return $this->canDocument($componentName);
    }

    /**
     * Get all allowed component actions for this user
     * Useful for disabling buttons/UI elements
     */
    public function getAllowedComponentActions(): array
    {
        $actions = [];

        // Collect all allowed permissions
        $permissions = $this->getDocumentGroupPermissions();

        foreach ($permissions as $permission) {
            $actions[$permission['name']] = true;
        }

        return $actions;
    }
}
