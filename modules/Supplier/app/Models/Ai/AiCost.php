<?php

namespace Modules\Supplier\Models\Ai;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Modules\Supplier\Database\Factories\Ai\AiCostFactory;
use Modules\Supplier\Models\Supplier\Supplier;

class AiCost extends Model
{
    use HasFactory;

    protected static function newFactory(): AiCostFactory
    {
        return AiCostFactory::new();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'supplier_ai_costs';

    /**
     * Disable updated_at timestamp (only created_at)
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'supplier_id',
        'content_id',
        'batch_id',
        'model',
        'operation_type',
        'input_tokens',
        'output_tokens',
        'input_cost',
        'output_cost',
        'web_search_cost',
        'web_search_calls',
        'request_id',
        'latency_ms',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'supplier_id' => 'integer',
            'content_id' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'input_cost' => 'decimal:6',
            'output_cost' => 'decimal:6',
            'web_search_cost' => 'decimal:6',
            'web_search_calls' => 'integer',
            'latency_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the supplier that owns this cost record.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope to filter by supplier.
     */
    public function scopeBySupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope to filter by model.
     */
    public function scopeByModel(Builder $query, string $model): Builder
    {
        return $query->where('model', $model);
    }

    /**
     * Scope to filter by operation type.
     */
    public function scopeByOperation(Builder $query, string $operationType): Builder
    {
        return $query->where('operation_type', $operationType);
    }

    /**
     * Scope to filter by batch.
     */
    public function scopeByBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope to filter costs from today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to filter costs from this week.
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope to filter costs from this month.
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Get total cost (computed field access).
     */
    public function getTotalCostAttribute(): float
    {
        return ($this->input_cost ?? 0) + ($this->output_cost ?? 0) + ($this->web_search_cost ?? 0);
    }

    /**
     * Get total tokens (computed field access).
     */
    public function getTotalTokensAttribute(): int
    {
        return $this->input_tokens + $this->output_tokens;
    }

    /**
     * Calculate cost per token.
     */
    public function getCostPerToken(): float
    {
        if ($this->total_tokens === 0) {
            return 0.0;
        }

        return $this->total_cost / $this->total_tokens;
    }

    /**
     * Calculate cost per 1K tokens.
     */
    public function getCostPer1kTokens(): float
    {
        return $this->getCostPerToken() * 1000;
    }

    /**
     * Get latency in seconds.
     */
    public function getLatencyInSeconds(): ?float
    {
        if (is_null($this->latency_ms)) {
            return null;
        }

        return $this->latency_ms / 1000;
    }

    /**
     * Static method to get total costs for a date range.
     */
    public static function getTotalCostForPeriod(string $from, string $to, ?int $supplierId = null): float
    {
        $query = self::query()
            ->select(DB::raw('SUM(COALESCE(input_cost, 0) + COALESCE(output_cost, 0) + COALESCE(web_search_cost, 0)) as total'))
            ->whereBetween('created_at', [$from, $to]);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return (float) $query->value('total') ?? 0.0;
    }

    /**
     * Static method to get total tokens for a date range.
     */
    public static function getTotalTokensForPeriod(string $from, string $to, ?int $supplierId = null): int
    {
        $query = self::query()
            ->select(DB::raw('SUM(input_tokens + output_tokens) as total'))
            ->whereBetween('created_at', [$from, $to]);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return (int) $query->value('total') ?? 0;
    }

    /**
     * Get costs grouped by model.
     */
    public static function getCostsByModel(string $from, string $to, ?int $supplierId = null): array
    {
        $query = self::query()
            ->select(
                'model',
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(input_tokens + output_tokens) as total_tokens'),
                DB::raw('SUM(COALESCE(input_cost, 0) + COALESCE(output_cost, 0) + COALESCE(web_search_cost, 0)) as total_cost')
            )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('model')
            ->orderByDesc('total_cost');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get costs grouped by operation type.
     */
    public static function getCostsByOperation(string $from, string $to, ?int $supplierId = null): array
    {
        $query = self::query()
            ->select(
                'operation_type',
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(input_tokens + output_tokens) as total_tokens'),
                DB::raw('SUM(COALESCE(input_cost, 0) + COALESCE(output_cost, 0) + COALESCE(web_search_cost, 0)) as total_cost')
            )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('operation_type')
            ->orderByDesc('total_cost');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get daily costs for a date range.
     */
    public static function getDailyCosts(string $from, string $to, ?int $supplierId = null): array
    {
        $query = self::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(input_tokens + output_tokens) as total_tokens'),
                DB::raw('SUM(COALESCE(input_cost, 0) + COALESCE(output_cost, 0) + COALESCE(web_search_cost, 0)) as total_cost'),
                DB::raw('AVG(latency_ms) as avg_latency')
            )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get average latency for a period.
     */
    public static function getAverageLatency(string $from, string $to, ?int $supplierId = null): ?float
    {
        $query = self::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('latency_ms');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return $query->avg('latency_ms');
    }

    /**
     * Check if daily budget is exceeded.
     */
    public static function isDailyBudgetExceeded(float $budgetLimit): bool
    {
        $todayCost = self::getTotalCostForPeriod(
            today()->startOfDay()->toDateTimeString(),
            today()->endOfDay()->toDateTimeString()
        );

        return $todayCost >= $budgetLimit;
    }

    /**
     * Check if monthly budget is exceeded.
     */
    public static function isMonthlyBudgetExceeded(float $budgetLimit): bool
    {
        $monthlyCost = self::getTotalCostForPeriod(
            now()->startOfMonth()->toDateTimeString(),
            now()->endOfMonth()->toDateTimeString()
        );

        return $monthlyCost >= $budgetLimit;
    }

    /**
     * Get cost summary for display.
     */
    public function getSummary(): array
    {
        return [
            'model' => $this->model,
            'operation_type' => $this->operation_type,
            'total_tokens' => $this->total_tokens,
            'total_cost' => round($this->total_cost, 6),
            'cost_per_1k_tokens' => round($this->getCostPer1kTokens(), 6),
            'latency_ms' => $this->latency_ms,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
