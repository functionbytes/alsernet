<?php

namespace Modules\Mailrelay\Jobs;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Notifications\SyncFailedNotification;
use Modules\Mailrelay\Services\MailRelayService;

/**
 * Sync Subscriber Job
 *
 * Synchronizes a single subscriber with Mailrelay API.
 * This job is dispatched by SyncMailrelayCommand and handles:
 * - Creating new subscribers in Mailrelay
 * - Updating existing subscribers
 * - Syncing subscriber groups
 * - Handling sync errors with retry logic
 *
 * Reference: FASE 7 section 7.3 - Subscriber Sync Job
 */
class SyncSubscriberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The subscriber to sync
     */
    public Subscriber $subscriber;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
        $this->tries = config('mailrelay.sync.max_retries', 3);
        $this->timeout = config('mailrelay.timeout', 30);
    }

    /**
     * Execute the job.
     */
    public function handle(MailRelayService $mailrelayService): void
    {
        Log::info('Starting subscriber sync', [
            'subscriber_id' => $this->subscriber->id,
            'email' => $this->subscriber->email,
            'mailrelay_id' => $this->subscriber->mailrelay_id,
        ]);

        try {
            // Determine if we need to create or update
            if ($this->subscriber->mailrelay_id) {
                $this->updateSubscriberInMailrelay($mailrelayService);
            } else {
                $this->createSubscriberInMailrelay($mailrelayService);
            }

            // Sync subscriber groups if enabled
            if (config('mailrelay.sync.sync_groups', true)) {
                $this->syncSubscriberGroups($mailrelayService);
            }

            // Update local record
            $this->subscriber->update([
                'last_synced_at' => now(),
            ]);

            Log::info('Subscriber sync completed successfully', [
                'subscriber_id' => $this->subscriber->id,
                'email' => $this->subscriber->email,
                'mailrelay_id' => $this->subscriber->mailrelay_id,
            ]);

        } catch (RequestException $e) {
            $this->handleSyncError($e);
        } catch (\Exception $e) {
            $this->handleSyncError($e);
        }
    }

    /**
     * Create subscriber in Mailrelay
     */
    protected function createSubscriberInMailrelay(MailRelayService $mailrelayService): void
    {
        $data = $this->prepareSubscriberData();

        Log::debug('Creating subscriber in Mailrelay', [
            'email' => $this->subscriber->email,
            'data' => $data,
        ]);

        try {
            $response = $mailrelayService->client->post(
                $mailrelayService->apiUrl.'/subscribers',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$mailrelayService->apiKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data,
                    'timeout' => config('mailrelay.timeout', 30),
                ]
            );

            $result = json_decode($response->getBody()->getContents(), true);

            // Extract Mailrelay subscriber ID from response
            $mailrelayId = $result['id'] ?? $result['data']['id'] ?? null;

            if (! $mailrelayId) {
                throw new \Exception('No subscriber ID returned from Mailrelay API');
            }

            // Update local subscriber with Mailrelay ID
            $this->subscriber->update([
                'mailrelay_id' => $mailrelayId,
            ]);

            Log::info('Subscriber created in Mailrelay', [
                'subscriber_id' => $this->subscriber->id,
                'mailrelay_id' => $mailrelayId,
            ]);

        } catch (RequestException $e) {
            // Check if subscriber already exists (409 Conflict)
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 409) {
                Log::info('Subscriber already exists in Mailrelay, attempting to fetch', [
                    'email' => $this->subscriber->email,
                ]);
                $this->fetchExistingSubscriber($mailrelayService);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Update subscriber in Mailrelay
     */
    protected function updateSubscriberInMailrelay(MailRelayService $mailrelayService): void
    {
        $data = $this->prepareSubscriberData();

        Log::debug('Updating subscriber in Mailrelay', [
            'mailrelay_id' => $this->subscriber->mailrelay_id,
            'data' => $data,
        ]);

        try {
            $response = $mailrelayService->client->put(
                $mailrelayService->apiUrl.'/subscribers/'.$this->subscriber->mailrelay_id,
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$mailrelayService->apiKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data,
                    'timeout' => config('mailrelay.timeout', 30),
                ]
            );

            Log::info('Subscriber updated in Mailrelay', [
                'subscriber_id' => $this->subscriber->id,
                'mailrelay_id' => $this->subscriber->mailrelay_id,
            ]);

        } catch (RequestException $e) {
            // Check if subscriber not found (404)
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 404) {
                Log::warning('Subscriber not found in Mailrelay, creating new', [
                    'subscriber_id' => $this->subscriber->id,
                    'mailrelay_id' => $this->subscriber->mailrelay_id,
                ]);

                // Reset mailrelay_id and create new subscriber
                $this->subscriber->update(['mailrelay_id' => null]);
                $this->createSubscriberInMailrelay($mailrelayService);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Fetch existing subscriber from Mailrelay by email
     */
    protected function fetchExistingSubscriber(MailRelayService $mailrelayService): void
    {
        try {
            $response = $mailrelayService->client->get(
                $mailrelayService->apiUrl.'/subscribers',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$mailrelayService->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'query' => [
                        'email' => $this->subscriber->email,
                    ],
                    'timeout' => config('mailrelay.timeout', 30),
                ]
            );

            $result = json_decode($response->getBody()->getContents(), true);
            $subscribers = $result['data'] ?? $result ?? [];

            if (! empty($subscribers) && isset($subscribers[0]['id'])) {
                $mailrelayId = $subscribers[0]['id'];

                $this->subscriber->update([
                    'mailrelay_id' => $mailrelayId,
                ]);

                Log::info('Found existing subscriber in Mailrelay', [
                    'subscriber_id' => $this->subscriber->id,
                    'mailrelay_id' => $mailrelayId,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to fetch existing subscriber from Mailrelay', [
                'email' => $this->subscriber->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync subscriber groups
     */
    protected function syncSubscriberGroups(MailRelayService $mailrelayService): void
    {
        if (! $this->subscriber->mailrelay_id) {
            return;
        }

        $groups = $this->subscriber->groups;

        if ($groups->isEmpty()) {
            return;
        }

        Log::debug('Syncing subscriber groups', [
            'subscriber_id' => $this->subscriber->id,
            'groups_count' => $groups->count(),
        ]);

        foreach ($groups as $group) {
            try {
                // Add subscriber to group in Mailrelay
                $mailrelayService->client->post(
                    $mailrelayService->apiUrl.'/groups/'.$group->mailrelay_group_id.'/subscribers',
                    [
                        'headers' => [
                            'Authorization' => 'Bearer '.$mailrelayService->apiKey,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'subscriber_id' => $this->subscriber->mailrelay_id,
                        ],
                        'timeout' => config('mailrelay.timeout', 30),
                    ]
                );

                Log::debug('Subscriber added to group', [
                    'subscriber_id' => $this->subscriber->id,
                    'group_id' => $group->id,
                    'mailrelay_group_id' => $group->mailrelay_group_id,
                ]);

            } catch (RequestException $e) {
                // Ignore 409 (already in group) errors
                if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 409) {
                    Log::debug('Subscriber already in group', [
                        'subscriber_id' => $this->subscriber->id,
                        'group_id' => $group->id,
                    ]);
                } else {
                    Log::error('Failed to add subscriber to group', [
                        'subscriber_id' => $this->subscriber->id,
                        'group_id' => $group->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Prepare subscriber data for API request
     */
    protected function prepareSubscriberData(): array
    {
        $data = [
            'email' => $this->subscriber->email,
            'name' => $this->subscriber->name,
            'status' => $this->subscriber->status->value,
        ];

        // Add custom fields if sync_metadata is enabled
        if (config('mailrelay.sync.sync_metadata', true)) {
            if ($this->subscriber->custom_fields && is_array($this->subscriber->custom_fields)) {
                $data['custom_fields'] = $this->subscriber->custom_fields;
            }

            if ($this->subscriber->metadata && is_array($this->subscriber->metadata)) {
                $data = array_merge($data, $this->subscriber->metadata);
            }
        }

        return $data;
    }

    /**
     * Handle sync errors
     */
    protected function handleSyncError(\Exception $exception): void
    {
        Log::error('Subscriber sync failed', [
            'subscriber_id' => $this->subscriber->id,
            'email' => $this->subscriber->email,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Check if we should retry
        if ($this->attempts() < $this->tries) {
            $delay = $this->calculateRetryDelay();

            Log::info('Retrying subscriber sync', [
                'subscriber_id' => $this->subscriber->id,
                'attempt' => $this->attempts() + 1,
                'delay_seconds' => $delay,
            ]);

            // Release job back to queue with delay
            $this->release($delay);
        } else {
            // Max retries reached, fail the job
            Log::error('Subscriber sync failed permanently after max retries', [
                'subscriber_id' => $this->subscriber->id,
                'email' => $this->subscriber->email,
                'attempts' => $this->attempts(),
            ]);

            // Notify administrators about the sync failure
            if (config('mailrelay.error_handling.notify_on_error', false)) {
                $this->notifyAdministrators($exception);
            }

            $this->fail($exception);
        }
    }

    /**
     * Calculate retry delay with exponential backoff
     *
     * @return int Delay in seconds
     */
    protected function calculateRetryDelay(): int
    {
        $retryConfig = config('mailrelay.retry', [
            'delay' => 100,
            'multiplier' => 2,
        ]);

        $baseDelay = $retryConfig['delay'] / 1000; // Convert ms to seconds
        $multiplier = $retryConfig['multiplier'];

        // Exponential backoff: baseDelay * (multiplier ^ attempt)
        return (int) ($baseDelay * pow($multiplier, $this->attempts() - 1));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::critical('Subscriber sync job failed permanently', [
            'subscriber_id' => $this->subscriber->id,
            'email' => $this->subscriber->email,
            'error' => $exception->getMessage(),
        ]);

        // Update subscriber to mark sync failure if tracking is enabled
        if (config('mailrelay.sync.track_history', true)) {
            $metadata = $this->subscriber->metadata ?? [];
            $metadata['last_sync_error'] = [
                'message' => $exception->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ];

            $this->subscriber->update([
                'metadata' => $metadata,
            ]);
        }

        // Notify administrators about the permanent failure
        if (config('mailrelay.error_handling.notify_on_error', false)) {
            $this->notifyAdministrators($exception);
        }
    }

    /**
     * Send failure notification to configured administrator emails.
     */
    protected function notifyAdministrators(\Exception $exception): void
    {
        $recipients = config('mailrelay.error_handling.notification_recipients', []);

        if (empty($recipients)) {
            return;
        }

        $subscriberInfo = [
            'id' => $this->subscriber->id,
            'email' => $this->subscriber->email,
        ];

        foreach ($recipients as $email) {
            try {
                Notification::route('mail', $email)
                    ->notify(new SyncFailedNotification($subscriberInfo, $exception->getMessage()));
            } catch (\Exception $e) {
                Log::error('Failed to send sync failure notification', [
                    'recipient' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
