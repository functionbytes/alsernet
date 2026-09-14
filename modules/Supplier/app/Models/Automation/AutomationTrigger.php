<?php

namespace Modules\Supplier\Models\Automation;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Supplier\Database\Factories\Automation\AutomationTriggerFactory;

/**
 * Supplier Automation Trigger Model
 *
 * Automatic workflow triggers for executing workflows based on various conditions.
 *
 * @property int $id
 * @property string $uid
 * @property string $name
 * @property string|null $description
 * @property string $trigger_type
 * @property array|null $trigger_config
 * @property int|null $workflow_id
 * @property array|null $source_filter
 * @property array|null $execution_context
 * @property array|null $trigger_conditions
 * @property bool $is_enabled
 * @property Carbon|null $last_triggered_at
 * @property int $total_triggers
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AutomationWorkflow|null $workflow
 */
class AutomationTrigger extends Model
{
    protected $table = 'supplier_automation_triggers';

    use HasFactory, HasUid;

    protected $fillable = [
        'uid',
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'workflow_id',
        'source_filter',
        'execution_context',
        'trigger_conditions',
        'is_enabled',
        'last_triggered_at',
        'total_triggers',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'source_filter' => 'array',
            'execution_context' => 'array',
            'trigger_conditions' => 'array',
            'is_enabled' => 'boolean',
            'last_triggered_at' => 'datetime',
            'total_triggers' => 'integer',
        ];
    }

    protected static function newFactory(): AutomationTriggerFactory
    {
        return AutomationTriggerFactory::new();
    }

    /**
     * Get the workflow that this trigger executes.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflow::class, 'workflow_id');
    }

    /**
     * Scope: Filter by trigger type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('trigger_type', $type);
    }

    /**
     * Scope: Filter enabled triggers
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: Filter disabled triggers
     */
    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('is_enabled', false);
    }

    /**
     * Scope: Get schedule triggers
     */
    public function scopeSchedule(Builder $query): Builder
    {
        return $query->where('trigger_type', 'schedule');
    }

    /**
     * Scope: Get webhook triggers
     */
    public function scopeWebhook(Builder $query): Builder
    {
        return $query->where('trigger_type', 'webhook');
    }

    /**
     * Scope: Get file upload triggers
     */
    public function scopeFileUpload(Builder $query): Builder
    {
        return $query->where('trigger_type', 'file_upload');
    }

    /**
     * Scope: Get source change triggers
     */
    public function scopeSourceChange(Builder $query): Builder
    {
        return $query->where('trigger_type', 'source_change');
    }

    /**
     * Scope: Get dependent triggers
     */
    public function scopeDependent(Builder $query): Builder
    {
        return $query->where('trigger_type', 'dependent');
    }

    /**
     * Check if the trigger can fire based on conditions
     */
    public function canTrigger(array $context = []): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if (! $this->workflow_id) {
            return false;
        }

        // Check trigger conditions if they exist
        if ($this->trigger_conditions) {
            return $this->evaluateConditions($this->trigger_conditions, $context);
        }

        return true;
    }

    /**
     * Fire the trigger
     */
    public function fire(array $context = []): bool
    {
        if (! $this->canTrigger($context)) {
            return false;
        }

        // Merge execution context with provided context
        $executionContext = array_merge(
            $this->execution_context ?? [],
            $context
        );

        // Update trigger statistics
        $this->increment('total_triggers');
        $this->update(['last_triggered_at' => now()]);

        // Here you would typically dispatch a job or event to execute the workflow
        // For example: SupplierAutomationWorkflowExecuted::dispatch($this->workflow, $executionContext);

        return true;
    }

    /**
     * Get cron expression for schedule triggers
     */
    public function getCronExpression(): ?string
    {
        if ($this->trigger_type !== 'schedule') {
            return null;
        }

        return $this->trigger_config['cron_expression'] ?? null;
    }

    /**
     * Get webhook URL for webhook triggers
     */
    public function getWebhookUrl(): ?string
    {
        if ($this->trigger_type !== 'webhook') {
            return null;
        }

        return $this->trigger_config['webhook_url'] ?? null;
    }

    /**
     * Check if this is a schedule trigger
     */
    public function isScheduleTrigger(): bool
    {
        return $this->trigger_type === 'schedule';
    }

    /**
     * Check if this is a webhook trigger
     */
    public function isWebhookTrigger(): bool
    {
        return $this->trigger_type === 'webhook';
    }

    /**
     * Check if this is a file upload trigger
     */
    public function isFileUploadTrigger(): bool
    {
        return $this->trigger_type === 'file_upload';
    }

    /**
     * Check if this is a source change trigger
     */
    public function isSourceChangeTrigger(): bool
    {
        return $this->trigger_type === 'source_change';
    }

    /**
     * Check if this is a dependent trigger
     */
    public function isDependentTrigger(): bool
    {
        return $this->trigger_type === 'dependent';
    }

    /**
     * Evaluate trigger conditions
     */
    protected function evaluateConditions(array $conditions, array $context): bool
    {
        // Simple condition evaluation - can be extended for complex logic
        foreach ($conditions as $key => $value) {
            if (! isset($context[$key]) || $context[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get dependent workflow ID
     */
    public function getDependentWorkflowId(): ?int
    {
        if (! $this->isDependentTrigger()) {
            return null;
        }

        return $this->trigger_config['depends_on_workflow'] ?? null;
    }

    /**
     * Get dependent workflow status
     */
    public function getDependentWorkflowStatus(): ?string
    {
        if (! $this->isDependentTrigger()) {
            return null;
        }

        return $this->trigger_config['depends_on_status'] ?? null;
    }
}
