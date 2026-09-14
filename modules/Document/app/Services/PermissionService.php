<?php

namespace Modules\Document\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Document\Entities\DocumentValidatorGroup;

class PermissionService
{
    public function can(User $user, string $action, ?string $profile = null): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        $permissionName = $this->buildPermissionName($action, $profile);

        // Verificar permiso Spatie
        if (! $user->can($permissionName)) {
            return false;
        }

        if ($this->isEmailAction($action)) {
            return $this->hasEmailActionAccess($user, $action);
        }

        return true;
    }

    public function getAvailableActions(User $user, ?string $profile = null): array
    {
        if ($user->hasRole('super-admin')) {
            return $this->getAllActions();
        }

        $available = [];

        // Verificar cada acción
        foreach ($this->getAllActions() as $action) {
            if ($this->can($user, $action, $profile)) {
                $available[] = $action;
            }
        }

        return $available;
    }

    public function getEmailActionsConfig(User $user, ?string $profile = null): array
    {
        if ($user->hasRole('super-admin')) {
            return [
                'enable_initial_request' => true,
                'enable_missing_docs' => true,
                'enable_reminder' => true,
                'enable_upload_confirmation' => true,
                'enable_approval' => true,
                'enable_rejection' => true,
                'enable_custom_email' => true,
            ];
        }

        $groupConfig = DocumentValidatorGroup::getEmailConfigurationsForUser($user);

        // Filtrar por permisos Spatie
        $finalConfig = [];
        foreach ($groupConfig as $action => $enabled) {
            $actionName = str_replace('enable_', '', $action);
            $actionName = str_replace('_', '-', $actionName);

            // Solo habilitar si tiene permiso Y configuración de grupo
            $finalConfig[$action] = $enabled && $this->can($user, $actionName, $profile);
        }

        return $finalConfig;
    }

    public function isInValidatorGroup(User $user): bool
    {
        return DocumentValidatorGroup::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Obtener grupos de validador a los que pertenece el usuario
     */
    public function getUserValidatorGroups(User $user): Collection
    {
        return DocumentValidatorGroup::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->where('is_active', true)
            ->get();
    }

    private function buildPermissionName(string $action, ?string $profile = null): string
    {
        $profile = $profile ?? 'administrative'; // Default profile

        return "{$profile}.documents.{$action}";
    }

    private function isEmailAction(string $action): bool
    {
        $emailActions = [
            'send-custom-email',
            'send-notification',
            'send-reminder',
            'send-missing',
            'send-approval',
            'send-rejection',
            'send-upload-confirmation',
        ];

        return in_array($action, $emailActions);
    }

    private function hasEmailActionAccess(User $user, string $action): bool
    {
        $groupConfig = DocumentValidatorGroup::getEmailConfigurationsForUser($user);

        // Mapear acción a configuración
        $configKey = match ($action) {
            'send-notification' => 'enable_initial_request',
            'send-custom-email' => 'enable_custom_email',
            'send-reminder' => 'enable_reminder',
            'send-missing' => 'enable_missing_docs',
            'send-approval' => 'enable_approval',
            'send-rejection' => 'enable_rejection',
            'send-upload-confirmation' => 'enable_upload_confirmation',
            default => null,
        };

        return $configKey ? ($groupConfig[$configKey] ?? false) : false;
    }

    private function getAllActions(): array
    {
        return [
            'manage',
            'view',
            'create',
            'edit',
            'update',
            'destroy',
            'admin-upload',
            'confirm-upload',
            'delete-single',
            'send-notification',
            'send-custom-email',
            'send-reminder',
            'send-missing',
            'send-approval',
            'send-rejection',
            'send-upload-confirmation',
            'add-note',
            'files',
            'files.get',
            'files.delete',
            'history',
            'import',
            'import-erp',
            'pending',
            'missing-documents',
            'resend-reminder',
            'store',
            'summary',
            'sync.all',
            'sync.by-order',
            'sync.by-order.query',
            'sync.from-erp',
        ];
    }
}
