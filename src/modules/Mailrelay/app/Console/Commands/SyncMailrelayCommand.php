<?php

namespace Modules\Mailrelay\Console\Commands;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Entities\MailrelayGroup;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Jobs\SyncSubscriberJob;
use Modules\Mailrelay\Services\MailRelayService;

/**
 * Sync Mailrelay Command
 *
 * Synchronizes subscribers and groups between local database and Mailrelay API.
 * Reference: FASE 7 section 7.3 - Command Implementation
 *
 * Usage:
 *   php artisan mailrelay:sync              # Normal sync
 *   php artisan mailrelay:sync --force      # Force sync all subscribers
 *   php artisan mailrelay:sync --dry-run    # Preview without making changes
 */
class SyncMailrelayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailrelay:sync
                            {--force : Force sync all subscribers regardless of last sync time}
                            {--dry-run : Preview sync operations without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync subscribers and groups with Mailrelay API';

    /**
     * Mailrelay service instance
     */
    protected MailRelayService $mailrelayService;

    /**
     * Sync statistics
     */
    protected array $stats = [
        'groups_synced' => 0,
        'groups_created' => 0,
        'groups_updated' => 0,
        'subscribers_queued' => 0,
        'subscribers_skipped' => 0,
        'errors' => 0,
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(MailRelayService $mailrelayService)
    {
        parent::__construct();
        $this->mailrelayService = $mailrelayService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $this->info('🚀 Starting Mailrelay synchronization...');

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        if ($isForce) {
            $this->info('🔄 FORCE MODE - All subscribers will be synced');
        }

        // Step 1: Test connection
        if (! $this->testConnection()) {
            $this->error('❌ Failed to connect to Mailrelay API');

            return Command::FAILURE;
        }

        // Step 2: Sync groups
        if (! $this->syncGroups($isDryRun)) {
            $this->error('❌ Failed to sync groups');

            return Command::FAILURE;
        }

        // Step 3: Dispatch subscriber sync jobs
        if (! $this->dispatchSubscriberSyncJobs($isForce, $isDryRun)) {
            $this->error('❌ Failed to dispatch subscriber sync jobs');

            return Command::FAILURE;
        }

        // Display summary
        $this->displaySummary();

        $this->info('✅ Mailrelay synchronization completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Test connection to Mailrelay API
     */
    protected function testConnection(): bool
    {
        $this->info('🔌 Testing Mailrelay API connection...');

        try {
            // Test connection by fetching a simple endpoint
            $response = $this->mailrelayService->client->get(
                $this->mailrelayService->apiUrl.'/subscribers',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->mailrelayService->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => config('mailrelay.timeout', 30),
                    'query' => [
                        'page' => 1,
                        'limit' => 1,
                    ],
                ]
            );

            if ($response->getStatusCode() === 200) {
                $this->info('✅ Successfully connected to Mailrelay API');

                return true;
            }

            $this->error('❌ Unexpected response code: '.$response->getStatusCode());

            return false;

        } catch (RequestException $e) {
            $this->error('❌ Connection failed: '.$e->getMessage());

            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();

                if ($statusCode === 401) {
                    $this->error('🔑 Invalid API key. Please check your MAILRELAY_API_KEY in .env');
                } elseif ($statusCode === 403) {
                    $this->error('🚫 Access forbidden. Please check API permissions');
                } elseif ($statusCode >= 500) {
                    $this->error('🔥 Mailrelay server error. Please try again later');
                }
            }

            Log::error('Mailrelay connection test failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return false;

        } catch (\Exception $e) {
            $this->error('❌ Unexpected error: '.$e->getMessage());
            Log::error('Unexpected error during Mailrelay connection test', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync groups from Mailrelay API
     */
    protected function syncGroups(bool $isDryRun): bool
    {
        $this->info('📋 Syncing groups from Mailrelay...');

        try {
            // Fetch groups from Mailrelay API
            $response = $this->mailrelayService->client->get(
                $this->mailrelayService->apiUrl.'/groups',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->mailrelayService->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => config('mailrelay.timeout', 30),
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            $groups = $data['data'] ?? $data ?? [];

            if (empty($groups)) {
                $this->warn('⚠️  No groups found in Mailrelay');

                return true;
            }

            $this->info('Found '.count($groups).' groups in Mailrelay');

            $progressBar = $this->output->createProgressBar(count($groups));
            $progressBar->start();

            foreach ($groups as $groupData) {
                try {
                    $this->syncGroup($groupData, $isDryRun);
                    $progressBar->advance();
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error('Failed to sync group', [
                        'group_id' => $groupData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info('✅ Groups synchronized successfully');

            return true;

        } catch (RequestException $e) {
            $this->error('❌ Failed to fetch groups from Mailrelay: '.$e->getMessage());
            Log::error('Failed to fetch groups from Mailrelay', [
                'error' => $e->getMessage(),
            ]);

            return false;

        } catch (\Exception $e) {
            $this->error('❌ Unexpected error during group sync: '.$e->getMessage());
            Log::error('Unexpected error during group sync', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync a single group
     */
    protected function syncGroup(array $groupData, bool $isDryRun): void
    {
        $mailrelayGroupId = $groupData['id'] ?? null;
        $name = $groupData['name'] ?? 'Unnamed Group';
        $description = $groupData['description'] ?? null;

        if (! $mailrelayGroupId) {
            $this->warn("⚠️  Skipping group without ID: {$name}");

            return;
        }

        if ($isDryRun) {
            $this->line("  [DRY RUN] Would sync group: {$name} (ID: {$mailrelayGroupId})");
            $this->stats['groups_synced']++;

            return;
        }

        // Find or create group
        $group = MailrelayGroup::where('mailrelay_group_id', $mailrelayGroupId)->first();

        if ($group) {
            // Update existing group
            $group->update([
                'name' => $name,
                'description' => $description,
                'last_synced_at' => now(),
            ]);
            $this->stats['groups_updated']++;
        } else {
            // Create new group
            MailrelayGroup::create([
                'mailrelay_group_id' => $mailrelayGroupId,
                'name' => $name,
                'description' => $description,
                'subscriber_count' => 0,
                'last_synced_at' => now(),
            ]);
            $this->stats['groups_created']++;
        }

        $this->stats['groups_synced']++;
    }

    /**
     * Dispatch subscriber sync jobs
     */
    protected function dispatchSubscriberSyncJobs(bool $isForce, bool $isDryRun): bool
    {
        $this->info('👥 Queuing subscriber sync jobs...');

        try {
            // Get subscribers that need syncing
            $query = Subscriber::query();

            if ($isForce) {
                $subscribersQuery = $query->active();
            } else {
                $subscribersQuery = $query->active()->where(function ($q) {
                    $q->whereNull('mailrelay_id')
                        ->orWhereNull('last_synced_at')
                        ->orWhere('last_synced_at', '<', now()->subHours(24));
                });
            }

            $count = $subscribersQuery->count();

            if ($count === 0) {
                $this->info('ℹ️  No subscribers need syncing');

                return true;
            }

            $this->info('Found '.$count.' subscribers to sync');

            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            $batchSize = config('mailrelay.sync.batch_size', 100);
            $useQueue = config('mailrelay.sync.use_queue', true);
            $queueName = config('mailrelay.sync.queue_name', 'mailrelay');

            $subscribersQuery->chunk(500, function ($subscribers) use (
                $isDryRun, $batchSize, $useQueue, $queueName, $progressBar
            ) {
                foreach ($subscribers as $subscriber) {
                    if ($isDryRun) {
                        $this->line("  [DRY RUN] Would queue sync job for: {$subscriber->email}");
                        $this->stats['subscribers_queued']++;
                    } else {
                        try {
                            // Dispatch sync job
                            if ($useQueue) {
                                SyncSubscriberJob::dispatch($subscriber)
                                    ->onQueue($queueName)
                                    ->delay(now()->addSeconds($this->stats['subscribers_queued'] % $batchSize));
                            } else {
                                // Sync immediately if queue is disabled
                                $this->syncSubscriber($subscriber);
                            }

                            $this->stats['subscribers_queued']++;
                        } catch (\Exception $e) {
                            $this->stats['errors']++;
                            $this->stats['subscribers_skipped']++;

                            Log::error('Failed to queue subscriber sync job', [
                                'subscriber_id' => $subscriber->id,
                                'email' => $subscriber->email,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $progressBar->advance();
                }
            });

            $progressBar->finish();
            $this->newLine(2);

            if ($isDryRun) {
                $this->info('✅ Subscriber sync jobs preview completed');
            } else {
                $this->info('✅ Subscriber sync jobs dispatched successfully');
            }

            return true;

        } catch (\Exception $e) {
            $this->error('❌ Failed to dispatch subscriber sync jobs: '.$e->getMessage());
            Log::error('Failed to dispatch subscriber sync jobs', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync a single subscriber (for non-queued mode)
     */
    protected function syncSubscriber(Subscriber $subscriber): void
    {
        try {
            // This is a simplified sync - in production, use the actual SyncSubscriberJob logic
            $data = [
                'email' => $subscriber->email,
                'name' => $subscriber->name,
                'status' => $subscriber->status->value,
            ];

            // Sync to Mailrelay API
            $response = $this->mailrelayService->client->post(
                $this->mailrelayService->apiUrl.'/subscribers',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->mailrelayService->apiKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data,
                    'timeout' => config('mailrelay.timeout', 30),
                ]
            );

            $result = json_decode($response->getBody()->getContents(), true);

            // Update subscriber with Mailrelay ID
            $subscriber->update([
                'mailrelay_id' => $result['id'] ?? null,
                'last_synced_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync subscriber', [
                'subscriber_id' => $subscriber->id,
                'email' => $subscriber->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Display sync summary
     */
    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('📊 Synchronization Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Groups Synced', $this->stats['groups_synced']],
                ['Groups Created', $this->stats['groups_created']],
                ['Groups Updated', $this->stats['groups_updated']],
                ['Subscribers Queued', $this->stats['subscribers_queued']],
                ['Subscribers Skipped', $this->stats['subscribers_skipped']],
                ['Errors', $this->stats['errors']],
            ]
        );
        $this->newLine();
    }
}
