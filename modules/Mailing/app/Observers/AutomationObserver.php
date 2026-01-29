<?php

namespace Modules\Mailing\Observers;

use Illuminate\Support\Str;
use Modules\Mailing\Models\Automation2;

/**
 * AutomationObserver
 *
 * Handles automatic tracking and lifecycle management for Automation models.
 * Migrated from Acelle Mail to Alsernet Mailing module.
 * Note: In Acelle, this is called Automation2 (version 2 of automation system)
 */
class AutomationObserver
{
    /**
     * Handle the Automation2 "creating" event.
     */
    public function creating(Automation2 $automation): void
    {
        // Generate unique identifier if not set
        if (empty($automation->uid)) {
            $automation->uid = $this->generateUid();
        }

        // Set default status
        if (empty($automation->status)) {
            $automation->status = 'inactive';
        }

        // Set default metadata
        if (empty($automation->metadata)) {
            $automation->metadata = [];
        }

        // Initialize statistics
        if (is_null($automation->total_subscribers)) {
            $automation->total_subscribers = 0;
        }

        if (is_null($automation->active_subscribers)) {
            $automation->active_subscribers = 0;
        }

        if (is_null($automation->completed_subscribers)) {
            $automation->completed_subscribers = 0;
        }
    }

    /**
     * Handle the Automation2 "created" event.
     */
    public function created(Automation2 $automation): void
    {
        // Create default trigger if not exists
        $this->createDefaultTrigger($automation);

        // Log automation creation
        activity()
            ->performedOn($automation)
            ->withProperties([
                'automation_name' => $automation->name,
                'automation_uid' => $automation->uid,
            ])
            ->log('Automation workflow created');

        // Clear automation cache
        $this->clearAutomationCache($automation);
    }

    /**
     * Handle the Automation2 "updating" event.
     */
    public function updating(Automation2 $automation): void
    {
        // Track status changes
        if ($automation->isDirty('status')) {
            $oldStatus = $automation->getOriginal('status');
            $newStatus = $automation->status;

            // Set activation/deactivation timestamps
            if ($newStatus === 'active' && empty($automation->activated_at)) {
                $automation->activated_at = now();
            }

            if ($newStatus === 'inactive' && $oldStatus === 'active') {
                $automation->deactivated_at = now();
            }

            activity()
                ->performedOn($automation)
                ->withProperties([
                    'automation_name' => $automation->name,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ])
                ->log('Automation status changed');

            // If deactivating, stop all running instances
            if ($newStatus === 'inactive') {
                $this->stopRunningInstances($automation);
            }
        }

        // Track name changes
        if ($automation->isDirty('name')) {
            activity()
                ->performedOn($automation)
                ->withProperties([
                    'old_name' => $automation->getOriginal('name'),
                    'new_name' => $automation->name,
                ])
                ->log('Automation renamed');
        }

        // Invalidate workflow cache if structure changed
        if ($automation->isDirty(['workflow_data', 'trigger_settings'])) {
            $this->clearWorkflowCache($automation);
        }
    }

    /**
     * Handle the Automation2 "updated" event.
     */
    public function updated(Automation2 $automation): void
    {
        // Clear automation cache
        $this->clearAutomationCache($automation);

        // Log significant updates
        if ($automation->wasChanged(['name', 'status', 'trigger_type', 'workflow_data'])) {
            activity()
                ->performedOn($automation)
                ->withProperties([
                    'changed_fields' => array_keys($automation->getChanges()),
                ])
                ->log('Automation updated');
        }

        // Rebuild workflow if structure changed
        if ($automation->wasChanged('workflow_data')) {
            $this->rebuildWorkflow($automation);
        }
    }

    /**
     * Handle the Automation2 "deleting" event.
     */
    public function deleting(Automation2 $automation): void
    {
        // Stop all running instances before deletion
        if ($automation->status === 'active') {
            $this->stopRunningInstances($automation);
        }

        // Log automation deletion
        activity()
            ->performedOn($automation)
            ->withProperties([
                'automation_name' => $automation->name,
                'total_subscribers' => $automation->total_subscribers,
                'completed_subscribers' => $automation->completed_subscribers,
            ])
            ->log('Automation deleted');
    }

    /**
     * Handle the Automation2 "deleted" event.
     */
    public function deleted(Automation2 $automation): void
    {
        // Clear all automation-related cache
        $this->clearAutomationCache($automation);
        $this->clearWorkflowCache($automation);

        // Delete related data if not using cascade
        // Triggers, timeline items, and subscriber automations should be handled by DB cascade
    }

    /**
     * Handle the Automation2 "forceDeleted" event.
     */
    public function forceDeleted(Automation2 $automation): void
    {
        $this->clearAutomationCache($automation);
        $this->clearWorkflowCache($automation);
    }

    /**
     * Generate a unique identifier for the automation
     */
    protected function generateUid(): string
    {
        do {
            $uid = uniqid(Str::random(10));
        } while (Automation2::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Create default trigger for new automation
     */
    protected function createDefaultTrigger(Automation2 $automation): void
    {
        // Check if AutoTrigger model exists
        if (! class_exists('Modules\Mailing\Models\AutoTrigger')) {
            return;
        }

        // Create a default trigger if none exists
        if (! $automation->triggers()->exists()) {
            $automation->triggers()->create([
                'name' => 'Default Trigger',
                'type' => 'on_subscribe',
                'settings' => [],
                'status' => 'active',
            ]);
        }
    }

    /**
     * Stop all running automation instances
     */
    protected function stopRunningInstances(Automation2 $automation): void
    {
        // Queue job to stop running instances
        dispatch(function () use ($automation) {
            try {
                // Get all active automation instances
                if (class_exists('Modules\Mailing\Models\SubscriberAutomation')) {
                    $automation->subscriberAutomations()
                        ->where('status', 'running')
                        ->update([
                            'status' => 'paused',
                            'paused_at' => now(),
                        ]);

                    activity()
                        ->performedOn($automation)
                        ->withProperties([
                            'automation_name' => $automation->name,
                        ])
                        ->log('All automation instances stopped');
                }
            } catch (\Exception $e) {
                logger()->error('Failed to stop automation instances', [
                    'automation_id' => $automation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    /**
     * Rebuild workflow structure
     */
    protected function rebuildWorkflow(Automation2 $automation): void
    {
        // Queue job to rebuild workflow
        dispatch(function () use ($automation) {
            try {
                // Rebuild automation workflow structure
                if (class_exists('Modules\Mailing\Services\AutomationWorkflowService')) {
                    $service = app('Modules\Mailing\Services\AutomationWorkflowService');
                    $service->rebuild($automation);

                    activity()
                        ->performedOn($automation)
                        ->withProperties([
                            'automation_name' => $automation->name,
                        ])
                        ->log('Automation workflow rebuilt');
                }
            } catch (\Exception $e) {
                logger()->error('Failed to rebuild automation workflow', [
                    'automation_id' => $automation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    /**
     * Clear automation-related cache
     */
    protected function clearAutomationCache(Automation2 $automation): void
    {
        // Clear cache for this specific automation
        cache()->forget("automation.{$automation->id}");
        cache()->forget("automation.uid.{$automation->uid}");

        // Clear automation list cache
        cache()->forget('automations.list');
        cache()->forget('automations.active');
        cache()->forget('automations.count');

        // Clear user-specific automation cache if available
        if ($automation->user_id) {
            cache()->forget("user.{$automation->user_id}.automations");
        }

        // Clear mail list specific cache
        if ($automation->mail_list_id) {
            cache()->forget("maillist.{$automation->mail_list_id}.automations");
        }

        // Clear automation statistics cache
        cache()->forget("automation.{$automation->id}.stats");
        cache()->forget("automation.{$automation->id}.performance");
    }

    /**
     * Clear workflow-related cache
     */
    protected function clearWorkflowCache(Automation2 $automation): void
    {
        // Clear workflow structure cache
        cache()->forget("automation.{$automation->id}.workflow");
        cache()->forget("automation.{$automation->id}.workflow.compiled");
        cache()->forget("automation.{$automation->id}.workflow.graph");

        // Clear element cache
        cache()->forget("automation.{$automation->id}.elements");

        // Clear trigger cache
        cache()->forget("automation.{$automation->id}.triggers");
    }
}
