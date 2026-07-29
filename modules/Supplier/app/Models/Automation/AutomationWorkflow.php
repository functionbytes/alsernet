<?php

namespace Modules\Supplier\Models\Automation;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * AutomationWorkflow Model
 *
 * Represents a workflow registered in the automation orchestrator.
 *
 * @property int $id
 * @property string $uid ULID unique identifier
 * @property string $name Workflow name
 * @property string|null $description
 * @property string $workflow_type Type: extraction, scraping, ftp_sync, content_generation, validation, publication, monitoring
 * @property string|null $external_workflow_id ID in orchestrator (n8n, Make, etc)
 * @property string|null $webhook_url Webhook URL to trigger workflow
 * @property string|null $callback_url Callback URL for workflow response
 * @property array|null $workflow_config Workflow configuration
 * @property array|null $default_variables Default variables for execution
 * @property int $timeout_seconds Execution timeout
 * @property int $max_retries Max retry attempts
 * @property int $priority Execution priority (1-10)
 * @property bool $is_active
 * @property Carbon|null $last_executed_at
 * @property int $total_executions
 * @property int $successful_executions
 * @property int $failed_executions
 * @property int|null $created_by FK to users
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $creator
 * @property-read Collection<int, AutomationExecution> $executions
 * @property-read int|null $executions_count
 */
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Supplier\Database\Factories\Automation\AutomationWorkflowFactory;

class AutomationWorkflow extends Model
{
    protected $table = 'supplier_automation_workflows';

    use HasFactory;
    use HasUid;

    protected $fillable = [
        'name',
        'description',
        'workflow_type',
        'external_workflow_id',
        'webhook_url',
        'callback_url',
        'workflow_config',
        'default_variables',
        'timeout_seconds',
        'max_retries',
        'priority',
        'is_active',
        'last_executed_at',
        'total_executions',
        'successful_executions',
        'failed_executions',
        'created_by',
    ];

    protected $casts = [
        'workflow_config' => 'json',
        'default_variables' => 'json',
        'timeout_seconds' => 'integer',
        'max_retries' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
        'total_executions' => 'integer',
        'successful_executions' => 'integer',
        'failed_executions' => 'integer',
    ];

    /**
     * Relationship to user who created the workflow
     */
    protected static function newFactory(): AutomationWorkflowFactory
    {
        return AutomationWorkflowFactory::new();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to executions
     */
    public function executions(): HasMany
    {
        return $this->hasMany(AutomationExecution::class, 'workflow_id');
    }

    /**
     * Relationship to workflow versions
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AutomationWorkflowVersion::class, 'workflow_id');
    }

    /**
     * Relationship to retry queue entries
     */
    public function retries(): HasMany
    {
        return $this->hasMany(AutomationRetryQueue::class, 'execution_id')
            ->whereHas('execution', function ($query) {
                $query->where('workflow_id', $this->id);
            });
    }

    /**
     * Scope: Filter by workflow type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('workflow_type', $type);
    }

    /**
     * Scope: Filter active workflows
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter inactive workflows
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Filter by priority
     */
    public function scopeWithPriority(Builder $query, int $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: Filter high priority workflows
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority', '>=', 7);
    }

    /**
     * Scope: Order by priority descending
     */
    public function scopeOrderByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Check if workflow is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Activate the workflow
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the workflow
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Increment total executions
     */
    public function incrementExecutions(): void
    {
        $this->increment('total_executions');
        $this->update(['last_executed_at' => now()]);
    }

    /**
     * Increment successful executions
     */
    public function incrementSuccessful(): void
    {
        $this->increment('successful_executions');
    }

    /**
     * Increment failed executions
     */
    public function incrementFailed(): void
    {
        $this->increment('failed_executions');
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_executions === 0) {
            return 0.0;
        }

        return round(($this->successful_executions / $this->total_executions) * 100, 2);
    }

    /**
     * Get failure rate percentage
     */
    public function getFailureRateAttribute(): float
    {
        if ($this->total_executions === 0) {
            return 0.0;
        }

        return round(($this->failed_executions / $this->total_executions) * 100, 2);
    }

    /**
     * Get recent executions
     */
    public function getRecentExecutions(int $limit = 10): Collection
    {
        return $this->executions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get execution statistics
     */
    public function getStatistics(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $executions = $this->executions()
            ->where('created_at', '>=', $since)
            ->get();

        return [
            'total' => $executions->count(),
            'completed' => $executions->where('status', 'completed')->count(),
            'failed' => $executions->where('status', 'failed')->count(),
            'running' => $executions->where('status', 'running')->count(),
            'pending' => $executions->where('status', 'pending')->count(),
            'avg_duration_ms' => $executions->where('duration_ms', '>', 0)->avg('duration_ms'),
            'max_duration_ms' => $executions->max('duration_ms'),
            'min_duration_ms' => $executions->where('duration_ms', '>', 0)->min('duration_ms'),
        ];
    }

    /**
     * Check if workflow is healthy (based on recent success rate)
     */
    public function isHealthy(int $hours = 24, float $minSuccessRate = 80.0): bool
    {
        $stats = $this->getStatistics($hours);

        if ($stats['total'] === 0) {
            return true; // No executions, assume healthy
        }

        $successRate = ($stats['completed'] / $stats['total']) * 100;

        return $successRate >= $minSuccessRate;
    }
}
