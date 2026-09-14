<?php

namespace Modules\Supplier\Models\Automation;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Supplier\Database\Factories\Automation\AutomationWorkflowVersionFactory;

/**
 * Supplier Automation Workflow Version Model
 *
 * Tracks versions of automation workflows for rollback and history.
 *
 * @property int $id
 * @property int $workflow_id
 * @property int $version
 * @property array $workflow_json
 * @property string|null $changelog
 * @property bool $is_active
 * @property Carbon|null $activated_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AutomationWorkflow $workflow
 * @property-read User|null $creator
 */
class AutomationWorkflowVersion extends Model
{
    protected $table = 'supplier_automation_workflow_versions';

    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'version',
        'workflow_json',
        'changelog',
        'is_active',
        'activated_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'workflow_id' => 'integer',
            'version' => 'integer',
            'workflow_json' => 'array',
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'created_by' => 'integer',
        ];
    }

    protected static function newFactory(): AutomationWorkflowVersionFactory
    {
        return AutomationWorkflowVersionFactory::new();
    }

    /**
     * Get the workflow this version belongs to.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflow::class, 'workflow_id');
    }

    /**
     * Get the user who created this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Filter by workflow
     */
    public function scopeForWorkflow(Builder $query, int $workflowId): Builder
    {
        return $query->where('workflow_id', $workflowId);
    }

    /**
     * Scope: Get active versions
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get inactive versions
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Get versions ordered by version number
     */
    public function scopeOrdered(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('version', $direction);
    }

    /**
     * Scope: Get latest version for a workflow
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('version', 'desc')->limit(1);
    }

    /**
     * Activate this version
     */
    public function activate(): void
    {
        // Deactivate all other versions for this workflow
        self::where('workflow_id', $this->workflow_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        // Activate this version
        $this->update([
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    /**
     * Deactivate this version
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get the next version number for a workflow
     */
    public static function getNextVersionNumber(int $workflowId): int
    {
        $latestVersion = self::where('workflow_id', $workflowId)
            ->max('version');

        return ($latestVersion ?? 0) + 1;
    }

    /**
     * Create a new version from workflow data
     */
    public static function createVersion(
        int $workflowId,
        array $workflowJson,
        ?string $changelog = null,
        ?int $createdBy = null,
        bool $activateImmediately = true
    ): self {
        $version = self::create([
            'workflow_id' => $workflowId,
            'version' => self::getNextVersionNumber($workflowId),
            'workflow_json' => $workflowJson,
            'changelog' => $changelog,
            'is_active' => false,
            'created_by' => $createdBy,
        ]);

        if ($activateImmediately) {
            $version->activate();
        }

        return $version;
    }

    /**
     * Get the active version for a workflow
     */
    public static function getActiveVersion(int $workflowId): ?self
    {
        return self::where('workflow_id', $workflowId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get a specific version by version number
     */
    public static function getVersion(int $workflowId, int $versionNumber): ?self
    {
        return self::where('workflow_id', $workflowId)
            ->where('version', $versionNumber)
            ->first();
    }

    /**
     * Get all versions for a workflow
     */
    public static function getAllVersions(int $workflowId): Collection
    {
        return self::where('workflow_id', $workflowId)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Rollback to this version
     */
    public function rollback(): void
    {
        $this->activate();

        // Here you would typically also sync with the orchestrator
        // For example: app(AutomationClient::class)->updateWorkflow($this->workflow->external_workflow_id, $this->workflow_json);
    }

    /**
     * Get the previous version
     */
    public function getPreviousVersion(): ?self
    {
        return self::where('workflow_id', $this->workflow_id)
            ->where('version', '<', $this->version)
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Get the next version
     */
    public function getNextVersion(): ?self
    {
        return self::where('workflow_id', $this->workflow_id)
            ->where('version', '>', $this->version)
            ->orderBy('version', 'asc')
            ->first();
    }

    /**
     * Check if this is the latest version
     */
    public function isLatestVersion(): bool
    {
        $latestVersion = self::where('workflow_id', $this->workflow_id)
            ->max('version');

        return $this->version === $latestVersion;
    }

    /**
     * Check if this is the first version
     */
    public function isFirstVersion(): bool
    {
        return $this->version === 1;
    }

    /**
     * Get changes compared to previous version
     */
    public function getChanges(): ?string
    {
        return $this->changelog;
    }

    /**
     * Compare with another version
     */
    public function compareWith(self $otherVersion): array
    {
        return [
            'current_version' => $this->version,
            'compared_version' => $otherVersion->version,
            'current_data' => $this->workflow_json,
            'compared_data' => $otherVersion->workflow_json,
            'is_newer' => $this->version > $otherVersion->version,
        ];
    }

    /**
     * Get version history summary
     */
    public function getHistorySummary(): array
    {
        return [
            'version' => $this->version,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'activated_at' => $this->activated_at,
            'created_by' => $this->creator?->name ?? 'Unknown',
            'changelog' => $this->changelog,
            'is_latest' => $this->isLatestVersion(),
        ];
    }

    /**
     * Clone this version as a new version
     */
    public function cloneAsNewVersion(?string $changelog = null, ?int $createdBy = null): self
    {
        return self::createVersion(
            $this->workflow_id,
            $this->workflow_json,
            $changelog ?? "Cloned from version {$this->version}",
            $createdBy ?? $this->created_by,
            false
        );
    }
}
