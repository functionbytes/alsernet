<?php

namespace Modules\Document\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Document\Entities\DocumentValidationHistory;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Enums\ValidationAction;

/**
 * Trait HasValidationWorkflow
 *
 * Provides multi-stage validation workflow functionality.
 * The model using this trait must have the following columns:
 *
 * Required columns:
 * - validation_status: enum('pending', 'in_validation', 'approved', 'rejected')
 * - current_stage: int (1-based)
 * - total_stages: int
 * - current_validator_group: string (key of ValidatorGroup)
 * - assigned_user_id: int (nullable, FK to users)
 * - validation_started_at: datetime (nullable)
 * - validation_completed_at: datetime (nullable)
 *
 * The model must also implement:
 * - validationHistory(): HasMany relationship to entity-specific history table
 * - getValidationWorkflowStages(): array of ValidatorGroup keys in order
 */
trait HasValidationWorkflow
{
    // =========================================================================
    // VALIDATION STATUS CONSTANTS
    // =========================================================================

    public const VALIDATION_STATUS_PENDING = 'pending';

    public const VALIDATION_STATUS_IN_VALIDATION = 'in_validation';

    public const VALIDATION_STATUS_APPROVED = 'approved';

    public const VALIDATION_STATUS_REJECTED = 'rejected';

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Assigned user relationship.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Current validator group relationship.
     */
    public function currentValidatorGroup(): ?DocumentValidatorGroup
    {
        if (empty($this->current_validator_group)) {
            return null;
        }

        return DocumentValidatorGroup::findByKey($this->current_validator_group);
    }

    // =========================================================================
    // WORKFLOW INITIALIZATION
    // =========================================================================

    /**
     * Initialize the validation workflow with the specified stages.
     *
     * @param  array<string>  $stageKeys  Array of ValidatorGroup keys in order
     */
    public function initializeValidationWorkflow(array $stageKeys): bool
    {
        if (empty($stageKeys)) {
            return false;
        }

        // Verify all groups exist
        $groups = DocumentValidatorGroup::getByKeysInOrder($stageKeys);
        if ($groups->count() !== count($stageKeys)) {
            return false;
        }

        $this->total_stages = count($stageKeys);
        $this->current_stage = 1;
        $this->validation_status = self::VALIDATION_STATUS_PENDING;
        $this->current_validator_group = $stageKeys[0];
        $this->assigned_user_id = null;
        $this->validation_started_at = null;
        $this->validation_completed_at = null;

        return $this->save();
    }

    /**
     * Start the validation process (move from pending to in_validation).
     */
    public function startValidation(): bool
    {
        if ($this->validation_status !== self::VALIDATION_STATUS_PENDING) {
            return false;
        }

        if ($this->total_stages < 1) {
            return false;
        }

        $this->validation_status = self::VALIDATION_STATUS_IN_VALIDATION;
        $this->validation_started_at = now();

        // Auto-assign user if group has assignment mode
        $this->autoAssignValidator();

        return $this->save();
    }

    // =========================================================================
    // STAGE APPROVAL & REJECTION
    // =========================================================================

    /**
     * Approve the current validation stage.
     *
     * @param  string|null  $comments  Optional comments for the approval
     * @param  User|null  $validator  The user approving (defaults to auth user)
     * @param  bool  $shouldSendEmail  Whether to send approval email (respects stage permissions)
     */
    public function approveCurrentStage(?string $comments = null, ?User $validator = null, bool $shouldSendEmail = false): bool
    {

        $validator = $validator ?? Auth::user();
        if (! $validator) {
            return false;
        }

        return DB::transaction(function () use ($comments, $validator, $shouldSendEmail) {
            // Record the approval in history
            $this->recordValidationAction('approved', $comments, $validator);

            // Determine if we should actually send email based on stage permissions
            $canSendEmail = $this->canPerformValidationAction(ValidationAction::SEND_APPROVAL_EMAIL);
            $sendEmailThisStage = $shouldSendEmail && $canSendEmail;

            // Move to next stage or complete
            if ($this->current_stage >= $this->total_stages) {
                // All stages completed
                $this->validation_status = self::VALIDATION_STATUS_APPROVED;
                $this->validation_completed_at = now();
                $this->current_validator_group = null;
                $this->assigned_user_id = null;
            } else {
                // Move to next stage
                $previousStage = $this->current_stage;
                $this->current_stage++;
                $nextGroup = $this->getStageGroupKey($this->current_stage);
                $this->current_validator_group = $nextGroup;
                $this->assigned_user_id = null;

                // Auto-assign new validator
                $this->autoAssignValidator();

                // Reset email flag if moving to intermediate stage that doesn't allow emails
                $sendEmailThisStage = false;

                // Notify all users in the next validator group (only if group is defined)
                if ($nextGroup !== null) {
                    $this->notifyValidatorGroup($previousStage, $this->current_stage, $nextGroup);
                }
            }

            $this->save();

            // Send approval email if allowed and requested
            if ($sendEmailThisStage) {
                event(new DocumentApprovalEmailRequested($this, $validator));
            }

            return true;
        });
    }

    /**
     * Reject the validation (entire workflow, not just current stage).
     *
     * @param  string|null  $comments  Required reason for rejection
     * @param  User|null  $validator  The user rejecting (defaults to auth user)
     */
    public function rejectValidation(?string $comments = null, ?User $validator = null): bool
    {
        if (! $this->canRejectValidation()) {
            return false;
        }

        // Check if REJECT action is allowed at current stage
        if (! $this->canPerformValidationAction(ValidationAction::REJECT)) {
            return false;
        }

        $validator = $validator ?? Auth::user();
        if (! $validator) {
            return false;
        }

        return DB::transaction(function () use ($comments, $validator) {
            // Record the rejection
            $this->recordValidationAction('rejected', $comments, $validator);

            $this->validation_status = self::VALIDATION_STATUS_REJECTED;
            $this->validation_completed_at = now();
            $this->assigned_user_id = null;

            return $this->save();
        });
    }

    /**
     * Reopen a rejected validation for re-submission.
     */
    public function reopenValidation(): bool
    {
        if ($this->validation_status !== self::VALIDATION_STATUS_REJECTED) {
            return false;
        }

        // Reset to first stage
        $this->validation_status = self::VALIDATION_STATUS_PENDING;
        $this->current_stage = 1;
        $this->current_validator_group = $this->getStageGroupKey(1);
        $this->assigned_user_id = null;
        $this->validation_completed_at = null;

        return $this->save();
    }

    // =========================================================================
    // PERMISSION CHECKS
    // =========================================================================

    /**
     * Check if the current stage can be approved.
     */
    public function canApproveStage(): bool
    {
        return $this->hasWorkflow()
            && $this->isInValidation()
            && $this->current_stage <= $this->total_stages;
    }

    /**
     * Check if the validation can be rejected.
     */
    public function canRejectValidation(): bool
    {
        return $this->isInValidation();
    }

    /**
     * Check if a specific user can validate the current stage.
     */
    public function canUserValidate(User|int $user): bool
    {
        if (! $this->canApproveStage()) {
            return false;
        }

        $group = $this->currentValidatorGroup();
        if (! $group) {
            return false;
        }

        return $group->canUserValidate($user);
    }

    /**
     * Get the validation permission service (lazy-loaded singleton).
     */
    public function getPermissionService(): \Modules\Document\Services\ValidationPermissionService
    {
        return app(\Modules\Document\Services\ValidationPermissionService::class);
    }

    /**
     * Check if a specific action is allowed at current validation stage.
     */
    public function canPerformValidationAction(ValidationAction $action): bool
    {
        $permissionService = $this->getPermissionService();

        return $permissionService->canPerformAction($this, $action);
    }

    /**
     * Get all allowed actions for the current validation stage.
     *
     * @return array<string> Array of allowed action values
     */
    public function getAllowedValidationActions(): array
    {
        $permissionService = $this->getPermissionService();

        return $permissionService->getAllowedActionsForDocument($this);
    }

    /**
     * Check if current stage is the first stage.
     */
    public function isFirstValidationStage(): bool
    {
        return $this->current_stage === 1;
    }

    /**
     * Check if current stage is the last stage.
     */
    public function isLastValidationStage(): bool
    {
        return $this->current_stage === $this->total_stages;
    }

    /**
     * Check if current stage is an intermediate stage (not first or last).
     */
    public function isIntermediateValidationStage(): bool
    {
        return ! $this->isFirstValidationStage() && ! $this->isLastValidationStage();
    }

    /**
     * Get stage-specific label for current validation stage.
     */
    public function getCurrentStageLabel(): string
    {
        $permissionService = $this->getPermissionService();

        return $permissionService->getStageLabelByKey($this->current_validator_group ?? '');
    }

    /**
     * Get stage-specific description for current validation stage.
     */
    public function getCurrentStageDescription(): string
    {
        $permissionService = $this->getPermissionService();

        return $permissionService->getStageDescriptionByKey($this->current_validator_group ?? '');
    }

    // =========================================================================
    // STATUS HELPERS
    // =========================================================================

    /**
     * Check if the entity has a validation workflow configured.
     */
    public function hasWorkflow(): bool
    {
        return $this->total_stages > 0;
    }

    /**
     * Check if the entity is currently in validation.
     */
    public function isInValidation(): bool
    {
        return in_array($this->validation_status, [
            self::VALIDATION_STATUS_PENDING,
            self::VALIDATION_STATUS_IN_VALIDATION,
        ]);
    }

    /**
     * Check if validation is complete (approved or rejected).
     */
    public function isValidationComplete(): bool
    {
        return in_array($this->validation_status, [
            self::VALIDATION_STATUS_APPROVED,
            self::VALIDATION_STATUS_REJECTED,
        ]);
    }

    /**
     * Check if validation was approved.
     */
    public function isApproved(): bool
    {
        return $this->validation_status === self::VALIDATION_STATUS_APPROVED;
    }

    /**
     * Check if validation was rejected.
     */
    public function isRejected(): bool
    {
        return $this->validation_status === self::VALIDATION_STATUS_REJECTED;
    }

    /**
     * Check if document is fully approved (all stages completed and approved).
     */
    public function isFullyApproved(): bool
    {
        return $this->isApproved() && $this->current_stage === $this->total_stages;
    }

    /**
     * Get the current progress percentage.
     */
    public function getValidationProgressPercentage(): float
    {
        if (! $this->hasWorkflow()) {
            return 0;
        }

        if ($this->isApproved()) {
            return 100;
        }

        return (($this->current_stage - 1) / $this->total_stages) * 100;
    }

    /**
     * Get validation status label.
     */
    public function getValidationStatusLabel(): string
    {
        return match ($this->validation_status) {
            self::VALIDATION_STATUS_PENDING => 'Pendiente',
            self::VALIDATION_STATUS_IN_VALIDATION => 'En validación',
            self::VALIDATION_STATUS_APPROVED => 'Aprobado',
            self::VALIDATION_STATUS_REJECTED => 'Rechazado',
            default => 'Desconocido',
        };
    }

    // =========================================================================
    // PROTECTED HELPERS
    // =========================================================================

    /**
     * Auto-assign a validator based on the current group's assignment mode.
     */
    protected function autoAssignValidator(): void
    {
        $group = $this->currentValidatorGroup();
        if (! $group) {
            return;
        }

        $user = $group->getNextUser(static::class);
        if ($user) {
            $this->assigned_user_id = $user->id;
        }
    }

    /**
     * Record a validation action in the history.
     * Uses polymorphic ValidationHistory model.
     */
    protected function recordValidationAction(string $action, ?string $comments, User $validator): void
    {
        $this->validationHistory()->create([
            'stage_number' => $this->current_stage,
            'validator_group' => $this->current_validator_group,
            'validator_user_id' => $validator->id,
            'action' => $action,
            'comments' => $comments,
            'validated_at' => now(),
        ]);
    }

    /**
     * Relationship to validation history.
     * Auto-implemented by trait.
     */
    public function validationHistory(): HasMany
    {
        return $this->hasMany(DocumentValidationHistory::class, 'document_id');
    }

    /**
     * Get the validator group key for a specific stage.
     * Override this method to customize stage-to-group mapping.
     */
    protected function getStageGroupKey(int $stage): ?string
    {
        // Default: use getValidationWorkflowStages if implemented
        if (method_exists($this, 'getValidationWorkflowStages')) {
            $stages = $this->getValidationWorkflowStages();

            return $stages[$stage - 1] ?? null;
        }

        // Fallback: keep current group
        return $this->current_validator_group;
    }

    /**
     * Notify all users in the validator group about the new stage assignment.
     */
    protected function notifyValidatorGroup(int $previousStage, int $currentStage, string $groupKey): void
    {
        try {
            // Get the validator group
            $validatorGroup = DocumentValidatorGroup::findByKey($groupKey);

            if (! $validatorGroup) {
                \Log::warning("Validator group not found: {$groupKey}");

                return;
            }

            // Get all users in this group
            $users = $validatorGroup->users()->get();

            if ($users->isEmpty()) {
                \Log::warning("No active users found in validator group: {$groupKey}");

                return;
            }

            // Send notification to all users in the group
            foreach ($users as $user) {
                $user->notify(new \Modules\Document\Notifications\DocumentStageAdvanced(
                    $this,
                    $previousStage,
                    $currentStage,
                    $groupKey
                ));
            }

            \Log::info('Stage advancement notifications sent', [
                'document_id' => $this->id,
                'previous_stage' => $previousStage,
                'current_stage' => $currentStage,
                'validator_group' => $groupKey,
                'users_notified' => $users->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending stage advancement notifications: '.$e->getMessage(), [
                'document_id' => $this->id,
                'validator_group' => $groupKey,
            ]);
        }
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter by validation status.
     */
    public function scopeValidationStatus($query, string $status)
    {
        return $query->where('validation_status', $status);
    }

    /**
     * Scope to get entities pending validation.
     */
    public function scopePendingValidation($query)
    {
        return $query->whereIn('validation_status', [
            self::VALIDATION_STATUS_PENDING,
            self::VALIDATION_STATUS_IN_VALIDATION,
        ]);
    }

    /**
     * Scope to get entities assigned to a specific user.
     */
    public function scopeAssignedTo($query, User|int $user)
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('assigned_user_id', $userId);
    }

    /**
     * Scope to get entities in a specific validator group.
     */
    public function scopeInValidatorGroup($query, string $groupKey)
    {
        return $query->where('current_validator_group', $groupKey);
    }

    // =========================================================================
    // STATIC HELPERS
    // =========================================================================

    /**
     * Count pending entities for a user (used for load balancing).
     */
    public static function countPendingForUser(int $userId): int
    {
        return static::query()
            ->where('assigned_user_id', $userId)
            ->whereIn('validation_status', [
                self::VALIDATION_STATUS_PENDING,
                self::VALIDATION_STATUS_IN_VALIDATION,
            ])
            ->count();
    }
}
