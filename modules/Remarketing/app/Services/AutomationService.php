<?php

namespace Modules\Remarketing\Services;

use Modules\Remarketing\Jobs\SendEmailJob;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\AutomationRun;
use Modules\Remarketing\Models\AutomationStep;
use Modules\Remarketing\Models\Customer;

class AutomationService
{
    /**
     * Create a new automation run for a customer if none is currently active.
     * Returns the created run or null if one already exists.
     */
    public function trigger(Automation $automation, Customer $customer, array $context = []): ?AutomationRun
    {
        $existing = AutomationRun::query()
            ->where('automation_id', $automation->id)
            ->where('customer_id', $customer->id)
            ->where('status', 'running')
            ->first();

        if ($existing) {
            return null;
        }

        return AutomationRun::query()->create([
            'automation_id' => $automation->id,
            'customer_id' => $customer->id,
            'current_step' => 0,
            'status' => 'running',
            'context' => $context,
            'next_step_at' => now(),
            'started_at' => now(),
        ]);
    }

    /**
     * Execute the current step for a run and advance to the next one.
     */
    public function advance(AutomationRun $run): void
    {
        $steps = $run->automation->steps()->orderBy('sort_order')->get();

        if ($run->current_step >= $steps->count()) {
            $run->update(['status' => 'completed', 'completed_at' => now()]);

            return;
        }

        /** @var AutomationStep $step */
        $step = $steps->get($run->current_step);

        $this->executeStep($run, $step);

        $nextIndex = $run->current_step + 1;

        if ($nextIndex >= $steps->count()) {
            $run->update(['status' => 'completed', 'completed_at' => now(), 'current_step' => $nextIndex]);

            return;
        }

        $run->update(['current_step' => $nextIndex]);
    }

    /**
     * Cancel an in-progress automation run.
     */
    public function cancel(AutomationRun $run): void
    {
        $run->update(['status' => 'cancelled', 'completed_at' => now()]);
    }

    /**
     * Execute a single step based on its type.
     */
    private function executeStep(AutomationRun $run, AutomationStep $step): void
    {
        $config = $step->config ?? [];

        match ($step->type) {
            'wait' => $this->executeWaitStep($run, $config),
            'send_email' => $this->executeSendEmailStep($run, $config),
            default => null,
        };
    }

    private function executeWaitStep(AutomationRun $run, array $config): void
    {
        $hours = (int) ($config['hours'] ?? 0);
        $run->update(['next_step_at' => now()->addHours($hours)]);
    }

    private function executeSendEmailStep(AutomationRun $run, array $config): void
    {
        if (class_exists(SendEmailJob::class)) {
            SendEmailJob::dispatch(null, $run->customer, $run, $config)
                ->onQueue('remarketing');
        }

        // Advance next_step_at immediately so the scheduler can pick up the next step
        $run->update(['next_step_at' => now()]);
    }
}
