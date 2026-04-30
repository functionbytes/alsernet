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
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;

class ProcessWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    private int $inboxId;

    /**
     * Create a new job instance.
     *
     * @param  Whatsapp  $whatsapp  The WhatsApp channel
     * @param  array  $message  WhatsApp message data from webhook
     * @param  string  $provider  Provider type (cloud_api, evolution)
     */
    public function __construct(
        public Whatsapp $whatsapp,
        public array $message,
        public string $provider
    ) {
        $this->onQueue('webhooks');

        // Eager load inbox relationship
        $this->whatsapp->loadMissing('inbox');
        $this->inboxId = $this->whatsapp->inbox->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            // Extract WhatsApp ID (phone number without '+')
            $fromWaId = $this->message['from'] ?? null;
            $messageId = $this->message['id'] ?? null;
            $timestamp = $this->message['timestamp'] ?? null;

            if (! $fromWaId || ! $messageId) {
                Log::warning('WhatsApp message missing required fields', [
                    'whatsapp_id' => $this->whatsapp->id,
                    'message' => $this->message,
                ]);

                return;
            }

            Log::info('Processing WhatsApp message', [
                'whatsapp_id' => $this->whatsapp->id,
                'provider' => $this->provider,
                'message_id' => $messageId,
                'from' => $fromWaId,
                'type' => $this->message['type'] ?? null,
            ]);

            // Extract message content
            $content = $this->extractMessageContent($this->message);

            if (! $content) {
                Log::warning('WhatsApp message has no content', [
                    'message_id' => $messageId,
                    'type' => $this->message['type'] ?? null,
                ]);

                return;
            }

            // Find or create customer
            $customer = $this->findOrCreateCustomer($fromWaId);

            if (! $customer) {
                Log::error('Failed to create customer for WhatsApp message', [
                    'wa_id' => $fromWaId,
                ]);

                return;
            }

            // Ensure CustomerInbox relationship exists (outside transaction)
            $this->ensureCustomerInboxExists($customer, $fromWaId);

            DB::beginTransaction();

            // Find or create conversation
            $conversation = $this->findOrCreateConversation($customer);

            if (! $conversation) {
                Log::error('Failed to create conversation for WhatsApp message', [
                    'customer_id' => $customer->id,
                ]);
                DB::rollBack();

                return;
            }

            // Create message
            $conversationMessage = ConversationMessage::create([
                'account_id' => $this->whatsapp->account_id,
                'conversation_id' => $conversation->id,
                'inbox_id' => $this->inboxId,
                'message_type' => 'incoming',
                'content' => $content['text'],
                'sender_type' => Customer::class,
                'sender_id' => $customer->id,
                'private' => false,
                'content_type' => $content['type'],
                'source_id' => $messageId,
                'status' => 'delivered',
                'created_at' => $timestamp ? date('Y-m-d H:i:s', $timestamp) : now(),
                'updated_at' => now(),
            ]);

            // Process attachments if present
            if (! empty($content['attachments'])) {
                $this->processAttachments($conversationMessage, $content['attachments']);
            }

            // Update conversation last activity (without triggering events)
            $conversation->updateQuietly([
                'last_activity_at' => now(),
                'last_message_at' => now(),
            ]);

            DB::commit();

            // Broadcast message sent event
            MessageSent::dispatch($conversationMessage);

            $endTime = microtime(true);
            $processingTime = ($endTime - $startTime) * 1000;

            Log::info('WhatsApp message processed successfully', [
                'whatsapp_id' => $this->whatsapp->id,
                'customer_id' => $customer->id,
                'conversation_id' => $conversation->id,
                'message_id' => $conversationMessage->id,
                'processing_time_ms' => round($processingTime, 2),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WhatsApp message processing failed', [
                'whatsapp_id' => $this->whatsapp->id,
                'message_id' => $this->message['id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Find or create a customer from WhatsApp ID.
     */
    private function findOrCreateCustomer(string $waId): ?Customer
    {
        $identifier = 'whatsapp_'.$waId;
        $phoneNumber = $this->formatPhoneNumber($waId);

        // Try to find existing customer
        $customer = Customer::where('account_id', $this->whatsapp->account_id)
            ->where('identifier', $identifier)
            ->first();

        if ($customer) {
            return $customer;
        }

        // Create new customer with fallback data
        $customer = Customer::create([
            'account_id' => $this->whatsapp->account_id,
            'name' => 'WhatsApp User',
            'email' => 'whatsapp_'.$waId.'@example.com',
            'phone_number' => $phoneNumber,
            'identifier' => $identifier,
            'avatar_url' => null,
            'additional_attributes' => [
                'whatsapp_id' => $waId,
                'whatsapp_phone' => $phoneNumber,
            ],
        ]);

        Log::info('Customer created for WhatsApp', [
            'customer_id' => $customer->id,
            'wa_id' => $waId,
            'phone' => $phoneNumber,
        ]);

        return $customer;
    }

    /**
     * Ensure CustomerInbox relationship exists.
     */
    private function ensureCustomerInboxExists(Customer $customer, string $waId): void
    {
        $customerInbox = CustomerInbox::where('customer_id', $customer->id)
            ->where('inbox_id', $this->inboxId)
            ->first();

        if (! $customerInbox) {
            CustomerInbox::create([
                'customer_id' => $customer->id,
                'inbox_id' => $this->inboxId,
                'source_id' => $waId,
                'hmac_verified' => true,
            ]);

            Log::info('CustomerInbox created for WhatsApp', [
                'customer_id' => $customer->id,
                'inbox_id' => $this->inboxId,
                'wa_id' => $waId,
            ]);
        }
    }

    /**
     * Find or create a conversation for the customer.
     */
    private function findOrCreateConversation(Customer $customer): ?Conversation
    {
        // Try to find existing open conversation
        $conversation = Conversation::where('account_id', $this->whatsapp->account_id)
            ->where('inbox_id', $this->inboxId)
            ->where('customer_id', $customer->id)
            ->whereHas('status', fn ($q) => $q->where('slug', 'open'))
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // Create new conversation
        $conversation = Conversation::create([
            'account_id' => $this->whatsapp->account_id,
            'inbox_id' => $this->inboxId,
            'customer_id' => $customer->id,
            'subject' => "WhatsApp from {$customer->name}",
            'status_id' => 1, // Open status
            'last_activity_at' => now(),
        ]);

        Log::info('Conversation created for WhatsApp message', [
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
        ]);

        return $conversation;
    }

    /**
     * Extract message content from WhatsApp message structure.
     */
    private function extractMessageContent(array $message): ?array
    {
        $type = $message['type'] ?? 'text';

        // Text message
        if ($type === 'text') {
            return [
                'type' => 'text',
                'text' => $message['text']['body'] ?? '',
                'attachments' => [],
            ];
        }

        // Image message
        if ($type === 'image') {
            return [
                'type' => 'image',
                'text' => '[IMAGE]',
                'attachments' => [
                    [
                        'type' => 'image',
                        'url' => $message['image']['id'] ?? null,
                        'caption' => $message['image']['caption'] ?? null,
                        'mime_type' => $message['image']['mime_type'] ?? null,
                    ],
                ],
            ];
        }

        // Video message
        if ($type === 'video') {
            return [
                'type' => 'video',
                'text' => '[VIDEO]',
                'attachments' => [
                    [
                        'type' => 'video',
                        'url' => $message['video']['id'] ?? null,
                        'caption' => $message['video']['caption'] ?? null,
                        'mime_type' => $message['video']['mime_type'] ?? null,
                    ],
                ],
            ];
        }

        // Audio message
        if ($type === 'audio') {
            return [
                'type' => 'audio',
                'text' => '[AUDIO]',
                'attachments' => [
                    [
                        'type' => 'audio',
                        'url' => $message['audio']['id'] ?? null,
                        'mime_type' => $message['audio']['mime_type'] ?? null,
                    ],
                ],
            ];
        }

        // Document message
        if ($type === 'document') {
            return [
                'type' => 'file',
                'text' => '[DOCUMENT]',
                'attachments' => [
                    [
                        'type' => 'document',
                        'url' => $message['document']['id'] ?? null,
                        'filename' => $message['document']['filename'] ?? null,
                        'caption' => $message['document']['caption'] ?? null,
                        'mime_type' => $message['document']['mime_type'] ?? null,
                    ],
                ],
            ];
        }

        // Location message
        if ($type === 'location') {
            $location = $message['location'] ?? [];

            return [
                'type' => 'text',
                'text' => sprintf(
                    '[LOCATION] %s - Lat: %s, Lon: %s',
                    $location['name'] ?? 'Location',
                    $location['latitude'] ?? '',
                    $location['longitude'] ?? ''
                ),
                'attachments' => [],
            ];
        }

        // Contacts message
        if ($type === 'contacts') {
            return [
                'type' => 'text',
                'text' => '[CONTACT SHARED]',
                'attachments' => [],
            ];
        }

        // Unknown type
        return [
            'type' => 'text',
            'text' => '[UNSUPPORTED MESSAGE TYPE: '.$type.']',
            'attachments' => [],
        ];
    }

    /**
     * Format phone number from WhatsApp ID to E.164 format.
     */
    private function formatPhoneNumber(string $waId): string
    {
        // WhatsApp IDs are phone numbers without '+' (e.g., 34612345678)
        // Convert to E.164 format: +34612345678
        return '+'.$waId;
    }

    /**
     * Process and store attachments from WhatsApp message.
     */
    private function processAttachments(ConversationMessage $message, array $attachments): void
    {
        $accessToken = $this->whatsapp->getApiCredential();
        $apiVersion = config('whatsapp.api_version', 'v24.0');
        $apiUrl = config('whatsapp.api_url', 'https://graph.facebook.com/');

        foreach ($attachments as $attachment) {
            try {
                $mediaId = $attachment['url'] ?? null;

                if (! $mediaId || ! $accessToken) {
                    continue;
                }

                // Get media URL from WhatsApp API
                $mediaInfoResponse = Http::withToken($accessToken)
                    ->get("{$apiUrl}{$apiVersion}/{$mediaId}");

                if ($mediaInfoResponse->failed()) {
                    Log::warning('Failed to get WhatsApp media info', [
                        'message_id' => $message->id,
                        'media_id' => $mediaId,
                    ]);

                    continue;
                }

                $mediaInfo = $mediaInfoResponse->json();
                $mediaUrl = $mediaInfo['url'] ?? null;

                if (! $mediaUrl) {
                    continue;
                }

                // Download file from WhatsApp
                $fileResponse = Http::withToken($accessToken)->get($mediaUrl);

                if ($fileResponse->failed()) {
                    Log::warning('Failed to download WhatsApp attachment', [
                        'message_id' => $message->id,
                        'media_url' => $mediaUrl,
                    ]);

                    continue;
                }

                // Generate filename
                $filename = $attachment['filename'] ?? $attachment['type'].'_'.time();

                // Store attachment with Spatie Media Library
                $message->addMediaFromString($fileResponse->body())
                    ->usingFileName($filename)
                    ->toMediaCollection('attachments');

                Log::info('WhatsApp attachment processed', [
                    'message_id' => $message->id,
                    'type' => $attachment['type'],
                    'filename' => $filename,
                ]);

            } catch (\Exception $e) {
                Log::error('Error processing WhatsApp attachment', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'whatsapp_id' => $this->whatsapp->id,
            'provider' => $this->provider,
            'message_id' => $this->message['id'] ?? null,
            'from' => $this->message['from'] ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
