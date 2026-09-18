<?php

namespace Modules\Supplier\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\Supplier\Models\Ai\AiBudget;

class AiBudgetObserver
{
    private const ALERTS_CACHE_KEY = 'supplier:ai:budget_alerts';

    public function saved(AiBudget $budget): void
    {
        Cache::forget(self::ALERTS_CACHE_KEY);
    }

    public function deleted(AiBudget $budget): void
    {
        Cache::forget(self::ALERTS_CACHE_KEY);
    }
}
