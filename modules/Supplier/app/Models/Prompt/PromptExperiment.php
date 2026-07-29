<?php

namespace Modules\Supplier\Models\Prompt;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PromptExperiment extends Model
{
    use HasUid;

    /**
     * The table associated with the model.
     */
    protected $table = 'supplier_prompt_experiments';

    /**
     * Disable updated_at timestamp (only created_at)
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uid',
        'name',
        'description',
        'prompt_type',
        'control_prompt_id',
        'variant_prompt_id',
        'traffic_split',
        'sample_size',
        'status',
        'started_at',
        'ended_at',
        'results',
        'winner_prompt_id',
        'statistical_significance',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'traffic_split' => 'decimal:2',
            'sample_size' => 'integer',
            'results' => 'array',
            'statistical_significance' => 'decimal:4',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::ulid();
            }
        });
    }

    /**
     * Get the control prompt.
     */
    public function controlPrompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'control_prompt_id');
    }

    /**
     * Get the variant prompt.
     */
    public function variantPrompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'variant_prompt_id');
    }

    /**
     * Get the winner prompt.
     */
    public function winnerPrompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'winner_prompt_id');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter draft experiments.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to filter running experiments.
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope to filter completed experiments.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter cancelled experiments.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to filter by prompt type.
     */
    public function scopePromptType(Builder $query, string $type): Builder
    {
        return $query->where('prompt_type', $type);
    }

    /**
     * Scope to filter active experiments (running or draft).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['draft', 'running']);
    }

    /**
     * Check if experiment is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if experiment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if experiment has a winner.
     */
    public function hasWinner(): bool
    {
        return ! is_null($this->winner_prompt_id);
    }

    /**
     * Get experiment duration in days.
     */
    public function getDurationInDays(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endDate = $this->ended_at ?? now();

        return $this->started_at->diffInDays($endDate);
    }

    /**
     * Get experiment duration in hours.
     */
    public function getDurationInHours(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endDate = $this->ended_at ?? now();

        return $this->started_at->diffInHours($endDate);
    }

    /**
     * Calculate sample progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if (! $this->results || ! isset($this->results['total_samples'])) {
            return 0.0;
        }

        $totalSamples = $this->results['total_samples'];

        if ($this->sample_size <= 0) {
            return 0.0;
        }

        return min(($totalSamples / $this->sample_size) * 100, 100.0);
    }

    /**
     * Check if experiment has reached required sample size.
     */
    public function hasReachedSampleSize(): bool
    {
        return $this->getProgressPercentage() >= 100;
    }

    /**
     * Get control prompt performance metrics.
     */
    public function getControlMetrics(): ?array
    {
        return $this->results['control'] ?? null;
    }

    /**
     * Get variant prompt performance metrics.
     */
    public function getVariantMetrics(): ?array
    {
        return $this->results['variant'] ?? null;
    }

    /**
     * Calculate improvement percentage of variant over control.
     */
    public function getImprovementPercentage(?string $metric = 'quality_score'): ?float
    {
        $control = $this->getControlMetrics();
        $variant = $this->getVariantMetrics();

        if (! $control || ! $variant || ! isset($control[$metric]) || ! isset($variant[$metric])) {
            return null;
        }

        $controlValue = $control[$metric];
        $variantValue = $variant[$metric];

        if ($controlValue == 0) {
            return null;
        }

        return (($variantValue - $controlValue) / $controlValue) * 100;
    }

    /**
     * Check if results are statistically significant.
     */
    public function isSignificant(?float $threshold = 0.05): bool
    {
        if (is_null($this->statistical_significance)) {
            return false;
        }

        return $this->statistical_significance <= $threshold;
    }

    /**
     * Start the experiment.
     */
    public function start(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $this->status = 'running';
        $this->started_at = now();

        return $this->save();
    }

    /**
     * Complete the experiment.
     */
    public function complete(?int $winnerPromptId = null): bool
    {
        if ($this->status !== 'running') {
            return false;
        }

        $this->status = 'completed';
        $this->ended_at = now();

        if ($winnerPromptId) {
            $this->winner_prompt_id = $winnerPromptId;
        }

        return $this->save();
    }

    /**
     * Cancel the experiment.
     */
    public function cancel(): bool
    {
        if (! in_array($this->status, ['draft', 'running'])) {
            return false;
        }

        $this->status = 'cancelled';
        $this->ended_at = now();

        return $this->save();
    }

    /**
     * Get experiment summary.
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'name' => $this->name,
            'prompt_type' => $this->prompt_type,
            'status' => $this->status,
            'progress' => $this->getProgressPercentage(),
            'duration_days' => $this->getDurationInDays(),
            'has_winner' => $this->hasWinner(),
            'is_significant' => $this->isSignificant(),
            'improvement' => $this->getImprovementPercentage(),
        ];
    }
}
