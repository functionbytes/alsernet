<?php

namespace Modules\Remarketing\Services;

use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\AutomationRun;
use Modules\Remarketing\Models\AutomationStep;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;

class AutomationService
{
    public function __construct(
        private readonly StepHandlerRegistry $registry
    ) {}

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
        if ($this->goalReached($run)) {
            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'goal_reached_at' => now(),
            ]);

            return;
        }

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
     * Check whether the automation's goal event has occurred for the customer
     * within the goal window since the run started.
     */
    protected function goalReached(AutomationRun $run): bool
    {
        $automation = $run->automation;

        if (! $automation->hasGoal() || ! $run->customer_id) {
            return false;
        }

        $since = $run->started_at;

        if ($automation->goal_window_hours) {
            $windowEnd = $run->started_at->copy()->addHours($automation->goal_window_hours);

            if (now()->greaterThan($windowEnd)) {
                return false;
            }
        }

        return Event::query()
            ->where('customer_id', $run->customer_id)
            ->where('type', $automation->goal_event)
            ->where('occurred_at', '>=', $since)
            ->exists();
    }

    /**
     * Cancel an in-progress automation run.
     */
    public function cancel(AutomationRun $run): void
    {
        $run->update(['status' => 'cancelled', 'completed_at' => now()]);
    }

    private function executeStep(AutomationRun $run, AutomationStep $step): void
    {
        $handler = $this->registry->get($step->type);

        if ($handler === null) {
            return;
        }

        $handler->execute($run, $step->config ?? []);
    }
}
