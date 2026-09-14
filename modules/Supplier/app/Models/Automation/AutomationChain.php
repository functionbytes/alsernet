<?php

namespace Modules\Supplier\Models\Automation;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Database\Factories\Automation\AutomationChainFactory;

class AutomationChain extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_automation_chains';

    protected static function newFactory(): AutomationChainFactory
    {
        return AutomationChainFactory::new();
    }

    protected $fillable = [
        'uid',
        'name',
        'description',
        'chain_definition',
        'fail_strategy',
        'parallel_stages',
        'max_parallel',
        'timeout_minutes',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'chain_definition' => 'array',
            'parallel_stages' => 'boolean',
            'max_parallel' => 'integer',
            'timeout_minutes' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * Get all executions of this chain.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(AutomationChainExecution::class, 'chain_id');
    }

    /**
     * Scope: Filter enabled chains
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: Filter disabled chains
     */
    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('is_enabled', false);
    }

    /**
     * Scope: Get chains by fail strategy
     */
    public function scopeByFailStrategy(Builder $query, string $strategy): Builder
    {
        return $query->where('fail_strategy', $strategy);
    }

    /**
     * Scope: Get chains with parallel execution
     */
    public function scopeParallel(Builder $query): Builder
    {
        return $query->where('parallel_stages', true);
    }

    /**
     * Get all stages defined in the chain
     */
    public function getStages(): array
    {
        return $this->chain_definition['stages'] ?? [];
    }

    /**
     * Get a specific stage by ID
     */
    public function getStage(string $stageId): ?array
    {
        $stages = $this->getStages();

        foreach ($stages as $stage) {
            if (($stage['id'] ?? null) === $stageId) {
                return $stage;
            }
        }

        return null;
    }

    /**
     * Get the first stage in the chain
     */
    public function getFirstStage(): ?array
    {
        $stages = $this->getStages();

        if (empty($stages)) {
            return null;
        }

        // Find stage with order = 1 or no dependencies
        foreach ($stages as $stage) {
            if (($stage['order'] ?? 0) === 1 || empty($stage['depends_on'] ?? [])) {
                return $stage;
            }
        }

        return $stages[0];
    }

    /**
     * Get stages that depend on a specific stage
     */
    public function getDependentStages(string $stageId): array
    {
        $stages = $this->getStages();
        $dependents = [];

        foreach ($stages as $stage) {
            $dependencies = $stage['depends_on'] ?? [];
            if (in_array($stageId, $dependencies)) {
                $dependents[] = $stage;
            }
        }

        return $dependents;
    }

    /**
     * Get stages that can run in parallel with a specific stage
     */
    public function getParallelStages(string $stageId): array
    {
        $stages = $this->getStages();
        $parallels = [];

        $targetStage = $this->getStage($stageId);
        if (! $targetStage) {
            return [];
        }

        $parallelWith = $targetStage['parallel_with'] ?? [];

        foreach ($stages as $stage) {
            $currentId = $stage['id'] ?? null;
            if ($currentId && in_array($currentId, $parallelWith)) {
                $parallels[] = $stage;
            }
        }

        return $parallels;
    }

    /**
     * Get the next stages after completing a stage
     */
    public function getNextStages(string $completedStageId): array
    {
        return $this->getDependentStages($completedStageId);
    }

    /**
     * Check if a stage requires approval
     */
    public function stageRequiresApproval(string $stageId): bool
    {
        $stage = $this->getStage($stageId);

        return $stage && ($stage['requires_approval'] ?? false);
    }

    /**
     * Get error handlers configuration
     */
    public function getErrorHandlers(): array
    {
        return $this->chain_definition['error_handlers'] ?? [];
    }

    /**
     * Get completion handlers configuration
     */
    public function getCompletionHandlers(): array
    {
        return $this->chain_definition['completion_handlers'] ?? [];
    }

    /**
     * Get stage error handler
     */
    public function getStageErrorHandler(): ?array
    {
        $handlers = $this->getErrorHandlers();

        return $handlers['on_stage_failure'] ?? null;
    }

    /**
     * Get chain error handler
     */
    public function getChainErrorHandler(): ?array
    {
        $handlers = $this->getErrorHandlers();

        return $handlers['on_chain_failure'] ?? null;
    }

    /**
     * Get success handler
     */
    public function getSuccessHandler(): ?array
    {
        $handlers = $this->getCompletionHandlers();

        return $handlers['on_success'] ?? null;
    }

    /**
     * Execute the chain
     */
    public function execute(array $context = [], ?int $triggeredByUserId = null, string $triggeredBy = 'manual'): AutomationChainExecution
    {
        return AutomationChainExecution::create([
            'chain_id' => $this->id,
            'status' => 'pending',
            'execution_context' => $context,
            'triggered_by' => $triggeredBy,
            'triggered_by_user' => $triggeredByUserId,
        ]);
    }

    /**
     * Get total stages count
     */
    public function getTotalStages(): int
    {
        return count($this->getStages());
    }

    /**
     * Validate chain definition structure
     */
    public function validateDefinition(): array
    {
        $errors = [];

        if (! isset($this->chain_definition['stages']) || ! is_array($this->chain_definition['stages'])) {
            $errors[] = 'Chain definition must have a stages array';
        }

        if (empty($this->chain_definition['stages'])) {
            $errors[] = 'Chain must have at least one stage';
        }

        // Validate each stage has required fields
        foreach ($this->getStages() as $index => $stage) {
            if (! isset($stage['id'])) {
                $errors[] = "Stage at index {$index} is missing 'id' field";
            }

            if (! isset($stage['workflow_id'])) {
                $stageIdentifier = $stage['id'] ?? $index;
                $errors[] = "Stage '{$stageIdentifier}' is missing 'workflow_id' field";
            }
        }

        return $errors;
    }

    /**
     * Check if chain definition is valid
     */
    public function isValid(): bool
    {
        return empty($this->validateDefinition());
    }

    /**
     * Get stages ordered by execution order
     */
    public function getStagesOrdered(): array
    {
        $stages = $this->getStages();

        usort($stages, function ($a, $b) {
            return ($a['order'] ?? 99) <=> ($b['order'] ?? 99);
        });

        return $stages;
    }
}
