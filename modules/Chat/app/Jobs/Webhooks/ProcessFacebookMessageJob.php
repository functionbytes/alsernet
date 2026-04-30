<?php

namespace Modules\Chat\Jobs\Webhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;
use Modules\Chat\Services\WebhookCacheService;

class ProcessFacebookMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    /**
     * Cached inbox ID to avoid N+1 queries on $this->facebookPage->inbox->id.
     */
    private int $inboxId;

    /**
     * Create a new job instance.
     *
     * @param  Facebook  $facebookPage  The Facebook page channel
     * @param  array  $events  Single event or array of events from same customer
     */
    public function __construct(
        public Facebook $facebookPage,
        public array $events
    ) {
        $this->onQueue('webhooks');

        // Eager load inbox relationship before job serialization
        // This prevents N+1 queries when accessing $this->facebookPage->inbox->id
        $this->facebookPage->loadMissing('inbox');

        // Cache inbox ID to avoid repeated relationship access
        $this->inboxId = $this->facebookPage->inbox->id;
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookCacheService $cacheService): void
    {
        $startTime = microtime(true);

        // Normalize to array of events (supports both single event and batch)
        $events = isset($this->events['sender']) ? [$this->events] : $this->events;
        $eventCount = count($events);

        try {
            // Group events by sender ID for batch processing
            $eventsBySender = [];
            foreach ($events as $event) {
                $senderId = $event['sender']['id'] ?? null;
                if (! $senderId) {
                    continue;
                }
                $eventsBySender[$senderId][] = $event;
            }

            // Process each sender's messages
            foreach ($eventsBySender as $senderId => $senderEvents) {
                $this->processSenderMessages($cacheService, $senderId, $senderEvents, $startTime);
            }

            $endTime = microtime(true);
            $processingTime = ($endTime - $startTime) * 1000;

            Log::info('[TIMING] Facebook batch processed', [
                'event_count' => $eventCount,
                'sender_count' => count($eventsBySender),
                'total_processing_time_ms' => round($processingTime, 2),
                'avg_time_per_event_ms' => round($processingTime / max($eventCount, 1), 2),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process all messages from a single sender.
     */
    private function processSenderMessages(WebhookCacheService $cacheService, string $senderId, array $events, float $startTime): void
    {
        // Validate first event for required fields
        $firstEvent = $events[0];
        $messageText = $firstEvent['message']['text'] ?? null;
        $attachments = $firstEvent['message']['attachments'] ?? null;

        if (! $messageText && ! $attachments) {
            Log::warning('Facebook message missing required fields', [
                'page_id' => $this->facebookPage->id,
                'sender_id' => $senderId,
            ]);

            return;
        }

        // Find or create customer (with cache)
        $customer = $this->findOrCreateCustomer($cacheService, $senderId);

        if (! $customer) {
            Log::error('Failed to create customer', [
                'facebook_sender_id' => $senderId,
            ]);

            return;
        }

        // Ensure CustomerInbox relationship exists (outside transaction)
        $this->ensureCustomerInboxExists($customer, $senderId);

        DB::beginTransaction();

        // Find or create conversation (with cache)
        $conversation = $this->findOrCreateConversation($cacheService, $customer);

        if (! $conversation) {
            Log::error('Failed to create conversation', [
                'customer_id' => $customer->id,
                'facebook_page_id' => $this->facebookPage->id,
            ]);
            DB::rollBack();

            return;
        }

        // Batch create all messages from this sender
        $messagesToInsert = [];
        $messageObjects = [];

        foreach ($events as $event) {
            $messageText = $event['message']['text'] ?? null;
            $messageMid = $event['message']['mid'] ?? null;
            $attachments = $event['message']['attachments'] ?? null;

            $contentType = 'text';
            $messageContent = $messageText ?? '';

            if ($attachments && ! $messageText) {
                $contentType = $this->getAttachmentContentType($attachments[0] ?? []);
                $messageContent = '['.strtoupper($contentType).' ATTACHMENT]';
            }

            $messagesToInsert[] = [
                'account_id' => $this->facebookPage->account_id,
                'conversation_id' => $conversation->id,
                'inbox_id' => $this->inboxId,
                'message_type' => 'incoming',
                'content' => $messageContent,
                'sender_type' => Customer::class,
                'sender_id' => $customer->id,
                'private' => false,
                'content_type' => $contentType,
                'source_id' => $messageMid,
                'status' => 'delivered',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Store event data for attachment processing
            $messageObjects[] = [
                'data' => end($messagesToInsert),
                'attachments' => $attachments,
            ];
        }

        // Batch insert all messages
        if (! empty($messagesToInsert)) {
            $firstInsertId = DB::table('chat_conversation_messages')->insertGetId(
                $messagesToInsert[0]
            );

            // For batch inserts of 2+ messages, insert remaining with bulk insert
            if (count($messagesToInsert) > 1) {
                DB::table('chat_conversation_messages')->insert(
                    array_slice($messagesToInsert, 1)
                );
            }

            // Calculate ID range for inserted messages (sequential auto-increment)
            // This is more efficient than ORDER BY + LIMIT on large conversations
            $lastInsertId = $firstInsertId + count($messagesToInsert) - 1;

            // Get inserted messages using ID range with eager loading
            $insertedMessages = ConversationMessage::whereBetween('id', [$firstInsertId, $lastInsertId])
                ->with('sender')
                ->orderBy('id', 'asc')
                ->get();

            // Process attachments and dispatch events
            foreach ($insertedMessages as $index => $message) {
                $messageObj = $messageObjects[$index] ?? null;
                if ($messageObj && $messageObj['attachments']) {
                    $this->processAttachments($message, $messageObj['attachments']);
                }

                // Broadcast message sent event (sender already eager loaded)
                MessageSent::dispatch($message);
            }
        }

        // Update conversation last activity (without triggering events)
        $conversation->updateQuietly([
            'last_activity_at' => now(),
            'last_message_at' => now(),
        ]);

        DB::commit();

        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;

        Log::info('Facebook messages processed successfully', [
            'facebook_page_id' => $this->facebookPage->id,
            'customer_id' => $customer->id,
            'conversation_id' => $conversation->id,
            'message_count' => count($events),
            'processing_time_ms' => round($processingTime, 2),
        ]);
    }

    /**
     * Find or create a customer from Facebook sender ID.
     */
    private function findOrCreateCustomer(WebhookCacheService $cacheService, string $senderId): ?Customer
    {
        $identifier = 'facebook_'.$senderId;

        // Try to find existing customer in cache first
        $customer = $cacheService->cacheGetCustomer($this->facebookPage->account_id, $identifier);

        if ($customer) {
            return $customer;
        }

        // Create customer with fallback data (fast, no HTTP blocking)
        // Profile will be fetched asynchronously in background
        $customer = Customer::create([
            'account_id' => $this->facebookPage->account_id,
            'name' => 'Facebook User',
            'email' => 'facebook_'.$senderId.'@example.com',
            'identifier' => $identifier,
            'avatar_url' => null,
            'additional_attributes' => [
                'facebook_id' => $senderId,
                'facebook_page_id' => $this->facebookPage->page_id,
            ],
        ]);

        // Warm cache with newly created customer
        $cacheService->cacheSetCustomer($customer);

        Log::info('Customer created (profile will be fetched async)', [
            'customer_id' => $customer->id,
            'facebook_sender_id' => $senderId,
        ]);

        // Dispatch async job to fetch and update profile (runs after transaction commits)
        FetchCustomerProfileJob::dispatch(
            $customer,
            $senderId,
            $this->facebookPage->page_access_token
        )->afterCommit();

        return $customer;
    }

    /**
     * Ensure CustomerInbox relationship exists.
     */
    private function ensureCustomerInboxExists(Customer $customer, string $facebookSenderId): void
    {
        $customerInbox = CustomerInbox::where('customer_id', $customer->id)
            ->where('inbox_id', $this->inboxId)
            ->first();

        if (! $customerInbox) {
            CustomerInbox::create([
                'customer_id' => $customer->id,
                'inbox_id' => $this->inboxId,
                'source_id' => $facebookSenderId,
                'hmac_verified' => true,
            ]);

            Log::info('CustomerInbox created for Facebook', [
                'customer_id' => $customer->id,
                'inbox_id' => $this->inboxId,
                'facebook_sender_id' => $facebookSenderId,
            ]);
        }
    }

    /**
     * Find or create a conversation for the customer.
     */
    private function findOrCreateConversation(WebhookCacheService $cacheService, Customer $customer): ?Conversation
    {
        // Try to find existing conversation in cache first
        $conversation = $cacheService->cacheGetConversation(
            $this->facebookPage->account_id,
            $customer->id,
            $this->inboxId
        );

        if ($conversation) {
            return $conversation;
        }

        // Create new conversation
        $conversation = Conversation::create([
            'account_id' => $this->facebookPage->account_id,
            'inbox_id' => $this->facebookPage->inbox->id,
            'customer_id' => $customer->id,
            'subject' => "Message from {$customer->name}",
            'status_id' => 1, // Open status
            'last_activity_at' => now(),
        ]);

        // Warm cache with newly created conversation
        $cacheService->cacheSetConversation($conversation);

        Log::info('Conversation created for Facebook message', [
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
        ]);

        return $conversation;
    }

    /**
     * Get content type from attachment.
     */
    private function getAttachmentContentType(array $attachment): string
    {
        $type = $attachment['type'] ?? 'file';

        return match ($type) {
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio',
            default => 'file',
        };
    }

    /**
     * Process and store attachments from Facebook message.
     */
    private function processAttachments(ConversationMessage $message, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            try {
                $url = $attachment['payload']['url'] ?? null;
                $type = $attachment['type'] ?? 'file';

                if (! $url) {
                    continue;
                }

                // Download file from Facebook
                $response = Http::get($url);

                if ($response->failed()) {
                    Log::warning('Failed to download Facebook attachment', [
                        'message_id' => $message->id,
                        'url' => $url,
                        'type' => $type,
                    ]);

                    continue;
                }

                // Generate filename from URL or use type
                $filename = basename(parse_url($url, PHP_URL_PATH)) ?: $type.'.'.time();

                // Store attachment with Spatie Media Library
                $message->addMediaFromString($response->body())
                    ->usingFileName($filename)
                    ->toMediaCollection('attachments');

                Log::info('Facebook attachment processed', [
                    'message_id' => $message->id,
                    'type' => $type,
                    'filename' => $filename,
                ]);
            } catch (\Exception $e) {
                Log::error('Error processing Facebook attachment', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        $events = isset($this->events['sender']) ? [$this->events] : $this->events;

        Log::error(static::class.' failed', [
            'facebook_page_id' => $this->facebookPage->id,
            'event_count' => count($events),
            'first_sender_id' => $events[0]['sender']['id'] ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
