<?php

namespace Modules\Remarketing\Services;

use Illuminate\Support\Str;
use Modules\Remarketing\Jobs\SendEmailJob;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\AutomationRun;
use Modules\Remarketing\Models\AutomationStep;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Template;

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
        $templateId = $config['template_id'] ?? null;
        $template = $templateId ? Template::query()->find($templateId) : null;
        $subject = $config['subject'] ?? $template?->subject ?? 'Sin asunto';

        $message = Message::query()->create([
            'store_id' => $run->automation->store_id,
            'customer_id' => $run->customer_id,
            'automation_run_id' => $run->id,
            'email' => $run->customer?->email ?? '',
            'subject' => $subject,
            'status' => 'queued',
            'open_token' => Str::random(64),
            'click_token' => Str::random(64),
        ]);

        if (class_exists(SendEmailJob::class)) {
            SendEmailJob::dispatch($message)
                ->onQueue('remarketing');
        }

        // Advance next_step_at immediately so the scheduler can pick up the next step
        $run->update(['next_step_at' => now()]);
    }
}
