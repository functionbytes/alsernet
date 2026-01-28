<?php

namespace Modules\Mailrelay\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Services\MailRelayService;

class SyncMailrelaySubscriberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The subscriber ID to sync.
     *
     * @var string
     */
    protected $subscriberId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $subscriberId)
    {
        $this->subscriberId = $subscriberId;
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(MailRelayService $mailRelayService): void
    {
        try {
            Log::info('Starting Mailrelay subscriber sync', [
                'subscriber_id' => $this->subscriberId,
                'attempt' => $this->attempts(),
            ]);

            // Find the subscriber
            $subscriber = Subscriber::findOrFail($this->subscriberId);

            // Check if subscriber is in a syncable state
            if (! $this->canSyncSubscriber($subscriber)) {
                Log::info('Subscriber cannot be synced at this time', [
                    'subscriber_id' => $this->subscriberId,
                    'status' => $subscriber->status->value,
                ]);

                return;
            }

            // Sync subscriber with Mailrelay
            $mailrelayId = $mailRelayService->syncSubscriber($subscriber);

            if ($mailrelayId) {
                Log::info('Successfully synced subscriber to Mailrelay', [
                    'subscriber_id' => $this->subscriberId,
                    'mailrelay_id' => $mailrelayId,
                ]);
            } else {
                throw new Exception('Failed to sync subscriber: no Mailrelay ID returned');
            }

        } catch (Exception $exception) {
            Log::error('Mailrelay subscriber sync failed', [
                'subscriber_id' => $this->subscriberId,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $exception;
        }
    }

    /**
     * Check if subscriber can be synced.
     */
    protected function canSyncSubscriber(Subscriber $subscriber): bool
    {
        // Only sync active subscribers
        if ($subscriber->status->value !== 'active') {
            return false;
        }

        // Ensure subscriber has required data
        if (empty($subscriber->email) || empty($subscriber->name)) {
            return false;
        }

        return true;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Mailrelay subscriber sync job permanently failed after all retries', [
            'subscriber_id' => $this->subscriberId,
            'attempts' => $this->tries,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        try {
            // Mark subscriber as needing manual review or retry later
            $subscriber = Subscriber::find($this->subscriberId);

            if ($subscriber) {
                // Update metadata to indicate sync failure
                $metadata = $subscriber->metadata ?? [];
                $metadata['sync_failed_at'] = now()->toDateTimeString();
                $metadata['sync_error'] = $exception->getMessage();
                $metadata['sync_attempts'] = $this->tries;

                $subscriber->update(['metadata' => $metadata]);

                Log::info('Updated subscriber metadata with sync failure information', [
                    'subscriber_id' => $this->subscriberId,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Could not update subscriber metadata after sync failure', [
                'subscriber_id' => $this->subscriberId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
