<?php

namespace Modules\Supplier\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Modules\Supplier\Database\Factories\Ai\AiBudgetFactory;

class AiBudget extends Model
{
    protected $table = 'supplier_ai_budgets';

    protected $fillable = [
        'provider',
        'monthly_limit',
        'daily_limit',
        'alert_threshold_pct',
        'is_active',
        'block_on_exceed',
        'notify_emails',
    ];

    protected function casts(): array
    {
        return [
            'monthly_limit' => 'decimal:2',
            'daily_limit' => 'decimal:2',
            'alert_threshold_pct' => 'decimal:2',
            'is_active' => 'boolean',
            'block_on_exceed' => 'boolean',
            'notify_emails' => 'array',
        ];
    }

    protected static function newFactory(): AiBudgetFactory
    {
        return AiBudgetFactory::new();
    }

    public function currentMonthUsage(): float
    {
        return $this->costForPeriod(
            now()->startOfMonth()->toDateTimeString(),
            now()->endOfMonth()->toDateTimeString()
        );
    }

    public function currentDayUsage(): float
    {
        return $this->costForPeriod(
            today()->startOfDay()->toDateTimeString(),
            today()->endOfDay()->toDateTimeString()
        );
    }

    private function costForPeriod(string $from, string $to): float
    {
        $prefix = match ($this->provider) {
            'openai'    => 'gpt-%',
            'anthropic' => 'claude-%',
            'google'    => 'gemini-%',
            default     => '%',
        };

        return (float) AiCost::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('model', 'LIKE', $prefix)
            ->selectRaw('SUM(COALESCE(input_cost, 0) + COALESCE(output_cost, 0) + COALESCE(web_search_cost, 0)) AS total')
            ->value('total') ?? 0.0;
    }

    public function usagePercentage(): float
    {
        if ($this->monthly_limit <= 0) {
            return 0.0;
        }

        return min(100, ($this->currentMonthUsage() / $this->monthly_limit) * 100);
    }

    public function dailyUsagePercentage(): float
    {
        if (! $this->daily_limit || $this->daily_limit <= 0) {
            return 0.0;
        }

        return min(100, ($this->currentDayUsage() / $this->daily_limit) * 100);
    }

    public function isAlertThresholdReached(): bool
    {
        return $this->usagePercentage() >= (float) $this->alert_threshold_pct;
    }

    public function isExceeded(): bool
    {
        return $this->usagePercentage() >= 100.0;
    }

    public function isDailyExceeded(): bool
    {
        return $this->daily_limit && $this->daily_limit > 0
            && $this->dailyUsagePercentage() >= 100.0;
    }

    /**
     * Determine if generation must be blocked for the given provider.
     * Returns the offending budget when blocking applies, null otherwise.
     */
    public static function shouldBlock(string $providerOrModel): ?self
    {
        $provider = self::providerForModel($providerOrModel);

        $budgets = self::query()
            ->where('is_active', true)
            ->where('block_on_exceed', true)
            ->where('provider', $provider)
            ->get();

        foreach ($budgets as $budget) {
            if ($budget->isExceeded() || $budget->isDailyExceeded()) {
                return $budget;
            }
        }

        return null;
    }

    private static function providerForModel(string $value): string
    {
        if (in_array($value, ['openai', 'anthropic', 'google'], true)) {
            return $value;
        }

        if (str_starts_with($value, 'claude')) return 'anthropic';
        if (str_starts_with($value, 'gemini')) return 'google';

        return 'openai';
    }
}
