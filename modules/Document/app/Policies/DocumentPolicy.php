<?php

namespace Modules\Document\Policies;

use App\Models\User;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Services\PermissionService;

class DocumentPolicy
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    public function viewAny(User $user, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'view', $profile);
    }

    public function view(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'view', $profile);
    }

    public function create(User $user, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'create', $profile);
    }

    public function manage(User $user, Document $document, ?string $profile = null): bool
    {
        // Super-admin siempre puede
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Verificar permiso base
        if (! $this->permissionService->can($user, 'manage', $profile)) {
            return false;
        }

        // Verificar si está en grupo validador activo
        return $this->permissionService->isInValidatorGroup($user);
    }

    public function update(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'update', $profile);
    }

    public function delete(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'destroy', $profile);
    }

    public function adminUpload(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'admin-upload', $profile);
    }

    public function confirmUpload(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'confirm-upload', $profile);
    }

    public function sendCustomEmail(User $user, Document $document, ?string $profile = null): bool
    {
        // Esta acción requiere verificación especial de email actions
        return $this->permissionService->can($user, 'send-custom-email', $profile);
    }

    public function sendNotification(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'send-notification', $profile);
    }

    public function sendReminder(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'send-reminder', $profile);
    }

    public function sendMissing(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'send-missing', $profile);
    }

    public function sendApproval(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'send-approval', $profile);
    }

    public function sendRejection(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'send-rejection', $profile);
    }

    public function sendUploadConfirmation(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'send-upload-confirmation', $profile);
    }

    public function addNote(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'add-note', $profile);
    }

    public function accessFiles(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'files', $profile);
    }

    public function viewHistory(User $user, Document $document, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'history', $profile);
    }

    public function import(User $user, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'import', $profile);
    }

    public function syncFromErp(User $user, ?string $profile = null): bool
    {
        return $this->permissionService->can($user, 'sync.from-erp', $profile);
    }

    public function approveStage(User $user, Document $document, ?string $profile = null): bool
    {
        // Verificar que esté en workflow activo
        if (! $document->total_stages || $document->validation_status !== 'in_validation') {
            return false;
        }

        // Verificar si es super-admin
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Verificar si el usuario pertenece al grupo validador actual
        $currentGroup = $document->current_validator_group;
        if (! $currentGroup) {
            return false;
        }

        $validatorGroup = DocumentValidatorGroup::findByKey($currentGroup);
        if (! $validatorGroup) {
            return false;
        }

        return $validatorGroup->canUserValidate($user);
    }

    public function rejectStage(User $user, Document $document, ?string $profile = null): bool
    {
        // Usar la misma lógica que approveStage
        return $this->approveStage($user, $document, $profile);
    }
}
