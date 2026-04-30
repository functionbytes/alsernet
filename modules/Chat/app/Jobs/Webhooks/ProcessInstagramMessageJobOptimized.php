<?php

namespace Modules\Chat\Jobs\Webhooks;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Models\Channels\Instagram;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;
use Modules\Chat\Services\WebhookCacheService;
use Modules\Chat\Traits\BatchProcessingTrait;

class ProcessInstagramMessageJobOptimized implements ShouldQueue
{
    use Batchable, BatchProcessingTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    private int $inboxId;

    /**
     * Create a new job instance.
     *
     * @param  Instagram  $instagram  The Instagram account channel
     * @param  array  $events  Single event or array of events from same customer
     */
    public function __construct(
        public Instagram $instagram,
        public array $events
    ) {
        $this->onQueue('webhooks');
        $this->instagram->loadMissing('inbox');
        $this->inboxId = $this->instagram->inbox->id;
    }

    /**
     * Execute the job with batch processing optimizations.
     */
    public function handle(WebhookCacheService $cacheService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $startTime = microtime(true);
        $events = isset($this->events['sender']) ? [$this->events] : $this->events;
        $eventCount = count($events);

        try {
            // Group events by sender ID
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
                $this->processSenderMessages($cacheService, $senderId, $senderEvents);
            }

            $this->logBatchMetrics('Instagram', $eventCount, count($eventsBySender), $startTime);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process all messages from a single sender with parallel attachment processing.
     */
    private function processSenderMessages(WebhookCacheService $cacheService, string $senderId, array $events): void
    {
        $firstEvent = $events[0];
        $messageText = $firstEvent['message']['text'] ?? null;
        $attachments = $firstEvent['message']['attachments'] ?? null;

        if (! $messageText && ! $attachments) {
            Log::warning('Instagram message missing required fields', [
                'channel_id' => $this->instagram->id,
                'sender_id' => $senderId,
            ]);

            return;
        }

        $customer = $this->findOrCreateCustomer($cacheService, $senderId);
        if (! $customer) {
            return;
        }

        $this->ensureCustomerInboxExists($customer, $senderId);

        DB::beginTransaction();

        $conversation = $this->findOrCreateConversation($cacheService, $customer);
        if (! $conversation) {
            DB::rollBack();

            return;
        }

        // Batch insert messages
        $messagesToInsert = [];
        $attachmentJobs = [];

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
                'account_id' => $this->instagram->account_id,
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
                '_attachments' => $attachments,
            ];
        }

        // Bulk insert all messages
        if (! empty($messagesToInsert)) {
            $cleanMessages = array_map(function ($msg) {
                unset($msg['_attachments']);

                return $msg;
            }, $messagesToInsert);

            DB::table('chat_conversation_messages')->insert($cleanMessages);

            $insertedMessages = ConversationMessage::where('conversation_id', $conversation->id)
                ->orderBy('id', 'desc')
                ->limit(count($messagesToInsert))
                ->get()
                ->reverse()
                ->values();

            // Dispatch parallel attachment jobs
            foreach ($insertedMessages as $index => $message) {
                $attachments = $messagesToInsert[$index]['_attachments'] ?? null;

                if ($attachments) {
                    foreach ($attachments as $attachment) {
                        $attachmentJobs[] = new ProcessAttachmentJob($message, $attachment);
                    }
                }

                $message->load('sender');
                MessageSent::dispatch($message);
            }

            // Dispatch attachment jobs as batch
            if (! empty($attachmentJobs)) {
                Bus::batch($attachmentJobs)
                    ->name('Instagram Attachments - Conversation '.$conversation->id)
                    ->allowFailures()
                    ->dispatch();
            }
        }

        $conversation->updateQuietly([
            'last_activity_at' => now(),
            'last_message_at' => now(),
        ]);

        DB::commit();

        Log::info('Instagram messages processed', [
            'conversation_id' => $conversation->id,
            'message_count' => count($events),
            'attachment_jobs' => count($attachmentJobs),
        ]);
    }

    private function findOrCreateCustomer(WebhookCacheService $cacheService, string $senderId): ?Customer
    {
        $identifier = 'instagram_'.$senderId;
        $customer = $cacheService->cacheGetCustomer($this->instagram->account_id, $identifier);

        if ($customer) {
            return $customer;
        }

        $customer = Customer::create([
            'account_id' => $this->instagram->account_id,
            'name' => 'Instagram User',
            'email' => 'instagram_'.$senderId.'@example.com',
            'identifier' => $identifier,
            'avatar_url' => null,
            'additional_attributes' => [
                'instagram_id' => $senderId,
                'instagram_username' => $this->instagram->username,
            ],
        ]);

        $cacheService->cacheSetCustomer($customer);

        return $customer;
    }

    private function ensureCustomerInboxExists(Customer $customer, string $instagramSenderId): void
    {
        $customerInbox = CustomerInbox::where('customer_id', $customer->id)
            ->where('inbox_id', $this->inboxId)
            ->first();

        if (! $customerInbox) {
            CustomerInbox::create([
                'customer_id' => $customer->id,
                'inbox_id' => $this->inboxId,
                'source_id' => $instagramSenderId,
                'hmac_verified' => true,
            ]);
        }
    }

    private function findOrCreateConversation(WebhookCacheService $cacheService, Customer $customer): ?Conversation
    {
        $conversation = $cacheService->cacheGetConversation(
            $this->instagram->account_id,
            $customer->id,
            $this->inboxId
        );

        if ($conversation) {
            return $conversation;
        }

        $conversation = Conversation::create([
            'account_id' => $this->instagram->account_id,
            'inbox_id' => $this->inboxId,
            'customer_id' => $customer->id,
            'subject' => "Message from {$customer->name}",
            'status_id' => 1,
            'last_activity_at' => now(),
        ]);

        $cacheService->cacheSetConversation($conversation);

        return $conversation;
    }

    private function getAttachmentContentType(array $attachment): string
    {
        $type = $attachment['type'] ?? 'file';
        $mediaType = $attachment['media']['image'] ?? $attachment['media']['video'] ?? null;

        return $mediaType ? 'image' : $type;
    }

    public function failed(\Throwable $exception): void
    {
        $events = isset($this->events['sender']) ? [$this->events] : $this->events;

        Log::error(static::class.' failed', [
            'instagram_id' => $this->instagram->id,
            'event_count' => count($events),
            'error' => $exception->getMessage(),
        ]);
    }
}
