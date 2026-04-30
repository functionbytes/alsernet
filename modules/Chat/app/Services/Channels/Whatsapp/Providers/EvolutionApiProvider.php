<?php

namespace Modules\Chat\Services\Channels\Whatsapp\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;
use Modules\Chat\Services\Channels\Whatsapp\AbstractWhatsappProvider;

class EvolutionApiProvider extends AbstractWhatsappProvider
{
    protected EvolutionApiService $service;

    public function __construct($whatsapp)
    {
        parent::__construct($whatsapp);
        $this->service = app(EvolutionApiService::class);
    }

    protected function getApiUrl(): string
    {
        return $this->whatsapp->getApiUrl();
    }

    protected function getApiCredential(): string
    {
        return $this->whatsapp->getApiCredential();
    }

    public function handleWebhook(Request $request, array $data): void
    {
        $event = $data['event'] ?? $request->input('event');
        $eventData = $data['data'] ?? $request->input('data');

        Log::info('Evolution API webhook', ['event' => $event]);

        match ($event) {
            'MESSAGES_UPSERT' => $this->handleMessageUpsert($eventData),
            'MESSAGES_UPDATE' => $this->handleMessageUpdate($eventData),
            'MESSAGES_DELETE' => $this->handleMessageDelete($eventData),
            'CONNECTION_UPDATE' => $this->handleConnectionUpdate($eventData),
            'QRCODE_UPDATED' => $this->handleQrCodeUpdate($eventData),
            'CONTACTS_UPSERT' => $this->handleContactsUpsert($eventData),
            'CONTACTS_UPDATE' => $this->handleContactsUpdate($eventData),
            'CHATS_UPDATE' => $this->handleChatsUpdate($eventData),
            'CALL' => $this->handleCall($eventData),
            default => Log::info('Unhandled Evolution API event', ['event' => $event]),
        };
    }

    public function sendTextMessage(string $to, string $message): array
    {
        return $this->service->sendTextMessage(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName(),
            $to,
            $message
        );
    }

    public function sendMediaMessage(string $to, string $mediaUrl, string $mediaType, ?string $caption = null): array
    {
        return $this->service->sendMediaMessage(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName(),
            $to,
            $mediaUrl,
            $mediaType,
            $caption
        );
    }

    public function sendLocationMessage(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null): array
    {
        return $this->service->sendLocationMessage(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName(),
            $to,
            $latitude,
            $longitude,
            $name,
            $address
        );
    }

    public function getConnectionStatus(): array
    {
        return $this->service->getConnectionState(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName()
        );
    }

    public function connect(): array
    {
        return $this->service->connect(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName()
        );
    }

    public function disconnect(): bool
    {
        return $this->service->logout(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName()
        );
    }

    public function restart(): array
    {
        return $this->service->restart(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName()
        );
    }

    public function getSettings(): array
    {
        return $this->service->getSettings(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName()
        );
    }

    public function updateSettings(array $settings): array
    {
        return $this->service->updateSettings(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName(),
            $settings
        );
    }

    public function fetchContacts(): array
    {
        return $this->service->fetchContacts(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName()
        );
    }

    public function fetchChats(): array
    {
        return $this->service->fetchChats(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName()
        );
    }

    public function deleteInstance(): bool
    {
        return $this->service->deleteInstance(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName()
        );
    }

    public function isWithinMessageWindow(): bool
    {
        return $this->service->isWithinMessageWindow();
    }

    public function getMessageTemplates(): array
    {
        // Evolution API doesn't use templates
        return [];
    }

    public function setWebhook(string $webhookUrl, array $events = []): array
    {
        return $this->service->setWebhook(
            $this->getApiUrl(),
            $this->getApiCredential(),
            $this->whatsapp->getInstanceName(),
            $webhookUrl,
            $events
        );
    }

    // ==================== EVENT HANDLERS ====================

    protected function handleMessageUpsert(array $data): void
    {
        try {
            $key = $data['key'] ?? [];
            $message = $data['message'] ?? [];

            if ($key['fromMe'] ?? false) {
                return;
            }

            $phoneNumber = str_replace(['@s.whatsapp.net', '@c.us', '@g.us'], '', $key['remoteJid'] ?? '');
            if (! $phoneNumber) {
                return;
            }

            $customerName = $data['pushName'] ?? $phoneNumber;
            $customer = $this->getOrCreateCustomer($phoneNumber, $customerName);
            $customerInbox = $this->getOrCreateCustomerInbox($customer, $phoneNumber);
            $conversation = $this->getOrCreateConversation($customer, $customerInbox);

            if ($conversation->isResolved()) {
                $conversation->markAsOpen();
            }

            $messageContent = $this->extractMessageContent($message);
            $messageType = $this->getMessageType($message);

            ConversationMessage::create([
                'account_id' => $this->whatsapp->account_id,
                'inbox_id' => $this->whatsapp->inbox->id,
                'conversation_id' => $conversation->id,
                'sender_id' => $customer->id,
                'sender_type' => Customer::class,
                'message_type' => 'incoming',
                'content_type' => $messageType,
                'content' => $messageContent,
                'source_id' => $key['id'] ?? null,
                'status' => 'sent',
            ]);

            $conversation->touch('last_activity_at');
        } catch (\Exception $e) {
            Log::error('Error in handleMessageUpsert', ['error' => $e->getMessage()]);
        }
    }

    protected function handleMessageUpdate(array $data): void
    {
        try {
            foreach ($data as $update) {
                $key = $update['key'] ?? [];
                $updateData = $update['update'] ?? [];

                $messageId = $key['id'] ?? null;
                $status = $updateData['status'] ?? null;

                if (! $messageId || ! $status) {
                    continue;
                }

                $statusMap = [
                    'PENDING' => 'pending',
                    'SERVER_ACK' => 'sent',
                    'DELIVERY_ACK' => 'delivered',
                    'READ' => 'read',
                    'ERROR' => 'failed',
                ];

                if (isset($statusMap[$status])) {
                    Message::where('source_id', $messageId)
                        ->where('inbox_id', $this->whatsapp->inbox->id)
                        ->update(['status' => $statusMap[$status]]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in handleMessageUpdate', ['error' => $e->getMessage()]);
        }
    }

    protected function handleMessageDelete(array $data): void
    {
        try {
            $key = $data['key'] ?? [];
            $messageId = $key['id'] ?? null;

            if ($messageId) {
                Message::where('source_id', $messageId)
                    ->where('inbox_id', $this->whatsapp->inbox->id)
                    ->update([
                        'content' => '[Mensaje eliminado]',
                        'deleted_at' => now(),
                    ]);

                Log::info('ConversationMessage deleted', ['message_id' => $messageId]);
            }
        } catch (\Exception $e) {
            Log::error('Error in handleMessageDelete', ['error' => $e->getMessage()]);
        }
    }

    protected function handleConnectionUpdate(array $data): void
    {
        $state = $data['state'] ?? null;

        Log::info('Connection update', ['state' => $state]);

        $attributes = $this->whatsapp->additional_attributes ?? [];
        $attributes['connection_state'] = $state;
        $attributes['last_connection_update'] = now()->toIso8601String();

        $this->whatsapp->update(['additional_attributes' => $attributes]);
    }

    protected function handleQrCodeUpdate(array $data): void
    {
        $qrcode = $data['qrcode'] ?? null;

        if ($qrcode) {
            $attributes = $this->whatsapp->additional_attributes ?? [];
            $attributes['qrcode'] = $qrcode['base64'] ?? $qrcode['code'] ?? null;
            $attributes['qrcode_updated_at'] = now()->toIso8601String();

            $this->whatsapp->update(['additional_attributes' => $attributes]);
        }
    }

    protected function handleContactsUpsert(array $data): void
    {
        try {
            $contactId = $data['id'] ?? null;
            $contactName = $data['name'] ?? null;
            $imgUrl = $data['imgUrl'] ?? null;

            if (! $contactId) {
                return;
            }

            $phoneNumber = str_replace(['@s.whatsapp.net', '@c.us'], '', $contactId);
            $customer = $this->getOrCreateCustomer($phoneNumber, $contactName ?? $phoneNumber);

            $updateData = [];
            if ($contactName && $contactName !== $phoneNumber) {
                $updateData['name'] = $contactName;
            }

            if ($imgUrl) {
                $attributes = $customer->additional_attributes ?? [];
                $attributes['profile_picture'] = $imgUrl;
                $updateData['additional_attributes'] = $attributes;
            }

            if (! empty($updateData)) {
                $customer->update($updateData);
            }

            Log::info('Customers upserted', ['phone' => $phoneNumber]);
        } catch (\Exception $e) {
            Log::error('Error in handleContactsUpsert', ['error' => $e->getMessage()]);
        }
    }

    protected function handleContactsUpdate(array $data): void
    {
        $this->handleContactsUpsert($data);
    }

    protected function handleChatsUpdate(array $data): void
    {
        try {
            $chatId = $data['id'] ?? null;
            $unreadCount = $data['unreadCount'] ?? null;
            $archived = $data['archived'] ?? false;

            if (! $chatId) {
                return;
            }

            $phoneNumber = str_replace(['@s.whatsapp.net', '@c.us', '@g.us'], '', $chatId);
            $customer = Customer::where('phone_number', $phoneNumber)
                ->where('account_id', $this->whatsapp->account_id)
                ->first();

            if ($customer) {
                $conversation = Conversation::where('customer_id', $customer->id)
                    ->where('inbox_id', $this->whatsapp->inbox->id)
                    ->first();

                if ($conversation) {
                    $attributes = $conversation->additional_attributes ?? [];
                    if ($unreadCount !== null) {
                        $attributes['unread_count'] = $unreadCount;
                    }
                    $attributes['archived'] = $archived;

                    $conversation->update(['additional_attributes' => $attributes]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in handleChatsUpdate', ['error' => $e->getMessage()]);
        }
    }

    protected function handleCall(array $data): void
    {
        try {
            $from = $data['from'] ?? null;
            $callId = $data['id'] ?? null;
            $status = $data['status'] ?? null;

            Log::info('Call received', ['from' => $from, 'status' => $status]);

            // If reject calls is enabled, the call is already rejected by Evolution API
            // We just log it here for tracking
        } catch (\Exception $e) {
            Log::error('Error in handleCall', ['error' => $e->getMessage()]);
        }
    }

    // ==================== HELPER METHODS ====================

    protected function getOrCreateCustomer(string $phoneNumber, string $name): Customer
    {
        return Customer::firstOrCreate(
            [
                'account_id' => $this->whatsapp->account_id,
                'phone_number' => $phoneNumber,
            ],
            [
                'name' => $name,
                'additional_attributes' => ['whatsapp' => true],
            ]
        );
    }

    protected function getOrCreateCustomerInbox(Customer $customer, string $sourceId): CustomerInbox
    {
        return CustomerInbox::firstOrCreate(
            [
                'inbox_id' => $this->whatsapp->inbox->id,
                'source_id' => $sourceId,
            ],
            [
                'customer_id' => $customer->id,
            ]
        );
    }

    protected function getOrCreateConversation(Customer $customer, CustomerInbox $customerInbox): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'account_id' => $this->whatsapp->account_id,
                'inbox_id' => $this->whatsapp->inbox->id,
                'customer_id' => $customer->id,
                'customer_inbox_id' => $customerInbox->id,
            ],
            [
                'status_id' => ConversationStatus::where('slug', 'open')->value('id'),
                'last_activity_at' => now(),
            ]
        );
    }

    protected function extractMessageContent(array $message): string
    {
        if (isset($message['conversation'])) {
            return $message['conversation'];
        }

        if (isset($message['extendedTextMessage']['text'])) {
            return $message['extendedTextMessage']['text'];
        }

        if (isset($message['imageMessage'])) {
            return $message['imageMessage']['caption'] ?? '[Image]';
        }

        if (isset($message['videoMessage'])) {
            return $message['videoMessage']['caption'] ?? '[Video]';
        }

        if (isset($message['audioMessage'])) {
            return '[Audio]';
        }

        if (isset($message['documentMessage'])) {
            return $message['documentMessage']['caption'] ?? '[Document]';
        }

        if (isset($message['locationMessage'])) {
            return '[Location]';
        }

        if (isset($message['contactMessage'])) {
            return '[Customers]';
        }

        return '[Unsupported message]';
    }

    protected function getMessageType(array $message): string
    {
        if (isset($message['conversation']) || isset($message['extendedTextMessage'])) {
            return 'text';
        }

        if (isset($message['imageMessage'])) {
            return 'image';
        }

        if (isset($message['videoMessage'])) {
            return 'video';
        }

        if (isset($message['audioMessage'])) {
            return 'audio';
        }

        if (isset($message['documentMessage'])) {
            return 'document';
        }

        if (isset($message['locationMessage'])) {
            return 'location';
        }

        if (isset($message['contactMessage'])) {
            return 'contact';
        }

        return 'text';
    }
}
