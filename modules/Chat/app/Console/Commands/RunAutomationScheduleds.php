<?php

namespace Modules\Chat\Console\Commands;

use Illuminate\Console\Command;
use Modules\Chat\Models\Automations\AutomationScheduled;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Services\Automation\AutomationRuleExecutor;

class RunAutomationScheduleds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automations:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled automations (time-based, SLA-based, inactive conversation)';

    /**
     * Execute the console command.
     */
    public function handle(AutomationRuleExecutor $executor): int
    {
        $automations = AutomationScheduled::where('active', true)->get();

        $executed = 0;

        foreach ($automations as $automation) {
            if (! $automation->shouldRun()) {
                continue;
            }

            $this->info("Running automation: {$automation->name}");

            $conversations = $this->getTargetConversations($automation);

            foreach ($conversations as $conversation) {
                foreach ($automation->actions as $action) {
                    $executor->executeAction($action, $conversation);
                }
            }

            $automation->markAsRun();
            $executed++;

            $this->info("  Processed {$conversations->count()} conversation");
        }

        $this->info("Executed {$executed} scheduled automations");

        return Command::SUCCESS;
    }

    /**
     * Get conversation that match automation conditions.
     */
    protected function getTargetConversations(AutomationScheduled $automation)
    {
        $query = Conversation::where('account_id', $automation->account_id);

        // Apply trigger type specific filters
        switch ($automation->trigger_type) {
            case 'inactive_conversation':
                // Conversations with no activity for X hours
                $hours = $automation->conditions['inactive_hours'] ?? 24;
                $query->where('last_activity_at', '<', now()->subHours($hours))
                    ->whereHas('status', fn ($q) => $q->where('slug', '!=', 'resolved'));
                break;

            case 'sla_based':
                // Conversations approaching SLA breach
                $query->whereNotNull('sla_id')
                    ->where('first_response_sla_breached', false)
                    ->whereNull('first_response_at');
                break;

            case 'time_based':
                // Apply custom conditions
                foreach ($automation->conditions as $condition) {
                    $this->applyCondition($query, $condition);
                }
                break;
        }

        return $query->with(['customer', 'inbox', 'assignee'])->get();
    }

    /**
     * Apply a single condition to query.
     */
    protected function applyCondition($query, array $condition): void
    {
        $attribute = $condition['attribute'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (! $attribute || ! $operator) {
            return;
        }

        match ($operator) {
            'equals' => $query->where($attribute, $value),
            'not_equals' => $query->where($attribute, '!=', $value),
            'is_present' => $query->whereNotNull($attribute),
            'is_not_present' => $query->whereNull($attribute),
            default => null,
        };
    }
}
