<?php

namespace Modules\Document\Services;

use Illuminate\Support\Facades\Config;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStageEmailAction;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Enums\ValidationAction;

class ValidationPermissionService
{
    /**
     * Check if a specific action is allowed in the current validation stage
     */
    public function canPerformAction(Document $document, ValidationAction $action): bool
    {
        if (! $document->hasWorkflow()) {
            return false;
        }

        $currentStageKey = $document->current_validator_group;
        if (empty($currentStageKey)) {
            return false;
        }

        return $this->isActionAllowedOnStage($currentStageKey, $action);
    }

    /**
     * Check if action is allowed on a specific stage
     */
    public function isActionAllowedOnStage(string $stageKey, ValidationAction $action): bool
    {
        $stageConfig = $this->getStageConfig($stageKey);

        if (! $stageConfig) {
            return false;
        }

        // Check if action is in allowed_actions
        $allowedActions = $stageConfig['allowed_actions'] ?? [];

        return in_array($action->value, $allowedActions, true);
    }

    /**
     * Get all allowed actions for a specific stage
     */
    public function getAllowedActionsForStage(string $stageKey): array
    {
        $stageConfig = $this->getStageConfig($stageKey);

        if (! $stageConfig) {
            return [];
        }

        return $stageConfig['allowed_actions'] ?? [];
    }

    /**
     * Get all allowed actions for a document's current stage
     */
    public function getAllowedActionsForDocument(Document $document): array
    {
        if (! $document->current_validator_group) {
            return [];
        }

        return $this->getAllowedActionsForStage($document->current_validator_group);
    }

    /**
     * Check if document can be approved at current stage
     */
    public function canApprove(Document $document): bool
    {
        return $this->canPerformAction($document, ValidationAction::APPROVE);
    }

    /**
     * Check if document can be rejected at current stage
     */
    public function canReject(Document $document): bool
    {
        return $this->canPerformAction($document, ValidationAction::REJECT);
    }

    /**
     * Check if approval email can be sent at current stage
     */
    public function canSendApprovalEmail(Document $document): bool
    {
        return $this->canPerformAction($document, ValidationAction::SEND_APPROVAL_EMAIL);
    }

    /**
     * Check if additional files can be accessed at current stage
     */
    public function canAccessAdditionalFiles(Document $document): bool
    {
        return $this->canPerformAction($document, ValidationAction::ACCESS_ADDITIONAL_FILES);
    }

    /**
     * Check if can move to next stage (without full approval)
     */
    public function canMoveToNextStage(Document $document): bool
    {
        return $this->canPerformAction($document, ValidationAction::MOVE_TO_NEXT_STAGE);
    }

    /**
     * Check if can add comments at current stage
     */
    public function canAddComments(Document $document): bool
    {
        return $this->canPerformAction($document, ValidationAction::ADD_COMMENT);
    }

    /**
     * Check if can request additional documents at current stage
     */
    public function canRequestAdditionalDocs(Document $document): bool
    {
        return $this->canPerformAction($document, ValidationAction::REQUEST_ADDITIONAL_DOCS);
    }

    /**
     * Get the stage configuration
     */
    public function getStageConfig(string $stageKey): ?array
    {
        $config = Config::get('validation-permissions');

        return $config[$stageKey] ?? null;
    }

    /**
     * Get stage label
     */
    public function getStageLabelByKey(string $stageKey): string
    {
        // First try to get from validator group database record (actual name)
        $validatorGroup = DocumentValidatorGroup::where('key', $stageKey)->first();

        if ($validatorGroup) {
            return $validatorGroup->name;
        }

        // Fallback to config (for backwards compatibility)
        $config = $this->getStageConfig($stageKey);

        return $config['label'] ?? ucfirst(str_replace('_', ' ', $stageKey));
    }

    /**
     * Get stage description
     */
    public function getStageDescriptionByKey(string $stageKey): string
    {
        $config = $this->getStageConfig($stageKey);

        return $config['description'] ?? '';
    }

    /**
     * Get all configured stages with their permissions
     */
    public function getAllStages(): array
    {
        $config = Config::get('validation-permissions');
        $stages = [];

        foreach ($config as $key => $stageConfig) {
            if (in_array($key, ['default_allowed_actions', 'rules'])) {
                continue;
            }

            $stages[$key] = $stageConfig;
        }

        return $stages;
    }

    /**
     * Get validation rules/constraints
     */
    public function getRules(): array
    {
        $config = Config::get('validation-permissions');

        return $config['rules'] ?? [];
    }

    /**
     * Check if a specific email action is enabled for a stage
     */
    public function isEmailActionEnabledForStage(string $stage, string $emailAction): bool
    {
        return DocumentStageEmailAction::isActionEnabledForStage($stage, $emailAction);
    }

    /**
     * Get all enabled email actions for a stage
     */
    public function getEnabledEmailActionsForStage(string $stage): array
    {
        return DocumentStageEmailAction::getEnabledActionsForStage($stage);
    }

    /**
     * Check if action respects rule: send approval email only on first/last stage
     */
    public function respectsSendEmailRule(Document $document, ValidationAction $action): bool
    {
        if ($action !== ValidationAction::SEND_APPROVAL_EMAIL) {
            return true;
        }

        $rules = $this->getRules();
        if (! ($rules['send_approval_email_only_first_last'] ?? false)) {
            return true;
        }

        // Check if current stage is first or last
        $isFirstStage = $document->current_stage === 1;
        $isLastStage = $document->current_stage === $document->total_stages;

        return $isFirstStage || $isLastStage;
    }

    /**
     * Check if action respects rule: reject only on first/last stage
     */
    public function respectsRejectRule(Document $document, ValidationAction $action): bool
    {
        if ($action !== ValidationAction::REJECT) {
            return true;
        }

        $rules = $this->getRules();
        if (! ($rules['reject_only_first_last'] ?? false)) {
            return true;
        }

        // Check if current stage is first or last
        $isFirstStage = $document->current_stage === 1;
        $isLastStage = $document->current_stage === $document->total_stages;

        return $isFirstStage || $isLastStage;
    }

    /**
     * Get a formatted description of allowed actions for a stage
     */
    public function getStagePermissionsDescription(string $stageKey): string
    {
        $config = $this->getStageConfig($stageKey);

        if (! $config) {
            return 'Etapa no configurada';
        }

        $allowedActions = array_map(
            fn (string $actionValue) => ValidationAction::tryFrom($actionValue)?->label() ?? $actionValue,
            $config['allowed_actions'] ?? []
        );

        return implode(', ', $allowedActions);
    }
}
