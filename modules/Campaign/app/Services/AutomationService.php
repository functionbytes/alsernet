<?php

namespace Modules\Campaign\Services;

use App\Jobs\RunAutomation;
use Modules\Campaign\Entities\Automation;

/**
 * Service class for automation-related business logic.
 *
 * Encapsulates automation operations and complex business logic
 * to keep controllers clean and promote code reusability.
 */
class AutomationService
{
    /**
     * Create a new automation instance.
     *
     * @param  array  $data  Automation data
     */
    public function create(array $data): Automation
    {
        $automation = new Automation;
        $automation->fill($data);
        $automation->save();

        return $automation;
    }

    /**
     * Update an existing automation.
     *
     * @param  Automation  $automation  The automation to update
     * @param  array  $data  Updated automation data
     */
    public function update(Automation $automation, array $data): Automation
    {
        $automation->update($data);

        return $automation;
    }

    /**
     * Delete an automation.
     *
     * @param  Automation  $automation  The automation to delete
     */
    public function delete(Automation $automation): bool
    {
        return $automation->delete();
    }

    /**
     * Execute an automation workflow.
     *
     * Queues the automation for background processing.
     *
     * @param  Automation  $automation  The automation to execute
     */
    public function execute(Automation $automation): bool
    {
        try {
            $automation->logger()->info(sprintf('Queuing automation "%s" for execution', $automation->name));
            dispatch(new RunAutomation($automation));

            return true;
        } catch (\Exception $exception) {
            \Log::error('Automation execution failed: '.$exception->getMessage(), [
                'automation_id' => $automation->id,
                'automation_uid' => $automation->uid,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    /**
     * Enable an automation.
     *
     * @param  Automation  $automation  The automation to enable
     */
    public function enable(Automation $automation): bool
    {
        try {
            $automation->update(['status' => 'active']);

            return true;
        } catch (\Exception $exception) {
            \Log::error('Automation enable failed: '.$exception->getMessage(), [
                'automation_id' => $automation->id,
                'automation_uid' => $automation->uid,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    /**
     * Disable an automation.
     *
     * @param  Automation  $automation  The automation to disable
     */
    public function disable(Automation $automation): bool
    {
        try {
            $automation->update(['status' => 'inactive']);

            return true;
        } catch (\Exception $exception) {
            \Log::error('Automation disable failed: '.$exception->getMessage(), [
                'automation_id' => $automation->id,
                'automation_uid' => $automation->uid,
                'exception' => $exception,
            ]);

            return false;
        }
    }
}
