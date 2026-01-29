<?php

namespace Modules\Mailing\Observers;

use Illuminate\Support\Str;
use Modules\Mailing\Models\SendingServer;

/**
 * SendingServerObserver
 *
 * Handles automatic tracking and lifecycle management for SendingServer models.
 * Migrated from Acelle Mail to Alsernet Mailing module.
 */
class SendingServerObserver
{
    /**
     * Handle the SendingServer "creating" event.
     */
    public function creating(SendingServer $server): void
    {
        // Generate unique identifier if not set
        if (empty($server->uid)) {
            $server->uid = $this->generateUid();
        }

        // Set default status
        if (empty($server->status)) {
            $server->status = 'active';
        }

        // Set default metadata
        if (empty($server->metadata)) {
            $server->metadata = [];
        }

        // Initialize quota tracking
        if (is_null($server->quota_value)) {
            $server->quota_value = 0;
        }

        if (is_null($server->quota_base)) {
            $server->quota_base = 0;
        }

        // Set default delivery rate
        if (is_null($server->sending_limit)) {
            $server->sending_limit = 1000; // Default: 1000 emails per hour
        }
    }

    /**
     * Handle the SendingServer "created" event.
     */
    public function created(SendingServer $server): void
    {
        // Test server connection if configured
        $this->testServerConnection($server);

        // Log server creation
        activity()
            ->performedOn($server)
            ->withProperties([
                'server_name' => $server->name,
                'server_type' => $server->type,
                'server_uid' => $server->uid,
            ])
            ->log('Sending server created');

        // Clear server cache
        $this->clearServerCache($server);
    }

    /**
     * Handle the SendingServer "updating" event.
     */
    public function updating(SendingServer $server): void
    {
        // Track status changes
        if ($server->isDirty('status')) {
            $oldStatus = $server->getOriginal('status');
            $newStatus = $server->status;

            activity()
                ->performedOn($server)
                ->withProperties([
                    'server_name' => $server->name,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ])
                ->log('Sending server status changed');

            // If server is being disabled, redistribute campaigns
            if ($newStatus === 'inactive') {
                $this->redistributeCampaigns($server);
            }
        }

        // Test connection if credentials changed
        if ($server->isDirty(['host', 'port', 'username', 'password', 'api_key'])) {
            $this->testServerConnection($server);
        }

        // Reset quota if quota settings changed
        if ($server->isDirty(['quota_value', 'quota_base', 'quota_unit'])) {
            $server->quota_used = 0;
            $server->quota_reset_at = now();
        }
    }

    /**
     * Handle the SendingServer "updated" event.
     */
    public function updated(SendingServer $server): void
    {
        // Clear server cache
        $this->clearServerCache($server);

        // Log significant updates
        if ($server->wasChanged(['name', 'type', 'status', 'sending_limit'])) {
            activity()
                ->performedOn($server)
                ->withProperties([
                    'changed_fields' => array_keys($server->getChanges()),
                ])
                ->log('Sending server updated');
        }

        // Update campaigns using this server
        $this->updateRelatedCampaigns($server);
    }

    /**
     * Handle the SendingServer "deleting" event.
     */
    public function deleting(SendingServer $server): void
    {
        // Check if server is being used
        $campaignCount = $this->getCampaignCount($server);

        // Log server deletion
        activity()
            ->performedOn($server)
            ->withProperties([
                'server_name' => $server->name,
                'campaigns_using' => $campaignCount,
            ])
            ->log('Sending server deleted');

        // Redistribute campaigns before deletion
        if ($campaignCount > 0) {
            $this->redistributeCampaigns($server);
        }
    }

    /**
     * Handle the SendingServer "deleted" event.
     */
    public function deleted(SendingServer $server): void
    {
        // Clear all server-related cache
        $this->clearServerCache($server);

        // Clear server statistics
        cache()->forget("sendingserver.{$server->id}.stats");
    }

    /**
     * Handle the SendingServer "forceDeleted" event.
     */
    public function forceDeleted(SendingServer $server): void
    {
        $this->clearServerCache($server);
    }

    /**
     * Generate a unique identifier for the sending server
     */
    protected function generateUid(): string
    {
        do {
            $uid = uniqid(Str::random(10));
        } while (SendingServer::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Test server connection
     */
    protected function testServerConnection(SendingServer $server): void
    {
        // Queue a job to test the connection asynchronously
        if (class_exists('Modules\Mailing\Jobs\TestSendingServer')) {
            dispatch(function () use ($server) {
                try {
                    // Test connection based on server type
                    $service = app('Modules\Mailing\Services\SendingServerTestService');
                    $result = $service->test($server);

                    // Update server status based on test result
                    $server->update([
                        'last_tested_at' => now(),
                        'test_result' => $result,
                    ]);
                } catch (\Exception $e) {
                    logger()->error('Sending server test failed', [
                        'server_id' => $server->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse();
        }
    }

    /**
     * Get number of campaigns using this server
     */
    protected function getCampaignCount(SendingServer $server): int
    {
        if (! class_exists('Modules\Mailing\Models\Campaign')) {
            return 0;
        }

        return $server->campaigns()->count();
    }

    /**
     * Redistribute campaigns to other servers
     */
    protected function redistributeCampaigns(SendingServer $server): void
    {
        if (! class_exists('Modules\Mailing\Models\Campaign')) {
            return;
        }

        // Queue job to redistribute campaigns
        dispatch(function () use ($server) {
            $campaigns = $server->campaigns()
                ->whereIn('status', ['draft', 'scheduled', 'paused'])
                ->get();

            if ($campaigns->isEmpty()) {
                return;
            }

            // Get alternative active server
            $alternativeServer = SendingServer::where('status', 'active')
                ->where('id', '!=', $server->id)
                ->where('type', $server->type)
                ->orderBy('quota_used', 'asc')
                ->first();

            if ($alternativeServer) {
                foreach ($campaigns as $campaign) {
                    $campaign->update(['sending_server_id' => $alternativeServer->id]);

                    activity()
                        ->performedOn($campaign)
                        ->withProperties([
                            'old_server' => $server->name,
                            'new_server' => $alternativeServer->name,
                        ])
                        ->log('Campaign server changed');
                }
            }
        })->afterResponse();
    }

    /**
     * Update campaigns using this server
     */
    protected function updateRelatedCampaigns(SendingServer $server): void
    {
        if (! class_exists('Modules\Mailing\Models\Campaign')) {
            return;
        }

        // Clear campaign cache for all campaigns using this server
        dispatch(function () use ($server) {
            $campaigns = $server->campaigns()->get();
            foreach ($campaigns as $campaign) {
                cache()->forget("campaign.{$campaign->id}");
                cache()->forget("campaign.{$campaign->id}.sending_server");
            }
        })->afterResponse();
    }

    /**
     * Clear sending server-related cache
     */
    protected function clearServerCache(SendingServer $server): void
    {
        // Clear cache for this specific server
        cache()->forget("sendingserver.{$server->id}");
        cache()->forget("sendingserver.uid.{$server->uid}");

        // Clear server list cache
        cache()->forget('sendingservers.list');
        cache()->forget('sendingservers.active');
        cache()->forget('sendingservers.count');

        // Clear type-specific cache
        cache()->forget("sendingservers.type.{$server->type}");

        // Clear user-specific server cache if available
        if ($server->user_id) {
            cache()->forget("user.{$server->user_id}.sendingservers");
        }

        // Clear server pool cache
        cache()->forget('sendingservers.pool');
        cache()->forget('sendingservers.available');
    }
}
