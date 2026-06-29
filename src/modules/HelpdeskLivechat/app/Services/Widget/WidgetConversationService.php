<?php

namespace Modules\HelpdeskLivechat\Services\Widget;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Services\WidgetSessionService;

class WidgetConversationService
{
    public function __construct(
        private readonly WidgetSessionService $sessionService
    ) {}

    /**
     * Create (or reuse) a conversation initiated from the widget.
     *
     * @param  array<string, mixed>  $data
     * @return array{conversation_id: int, customer_id: int, reused: bool}
     */
    public function createConversation(string $websiteToken, array $data): array
    {
        $web = Web::where('website_token', $websiteToken)->first();
        if (! $web) {
            throw new \RuntimeException('Invalid widget token');
        }

        return DB::connection('helpdesk')->transaction(function () use ($web, $data) {
            $inbox = Inbox::firstOrCreate(
                ['channel_type' => 'web', 'channel_id' => $web->id],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => $web->name ?? 'Widget Web',
                    'is_active' => true,
                ]
            );

            $email = $data['email'] ?? null;
            if (empty($email)) {
                $email = 'guest-'.Str::random(8).'@anonymous.local';
            }

            $customerDefaults = [
                'name' => $data['name'] ?? 'Anonymous',
                'phone' => $data['phone_number'] ?? null,
                'language' => $data['language'] ?? 'es',
            ];

            $customer = null;

            // If the widget already knows a customer_id, prefer it and update
            // email/name when the visitor has logged in since the last message.
            if (! empty($data['customer_id'])) {
                $customer = Customer::find($data['customer_id']);
                if ($customer && $email && $customer->email !== $email) {
                    $customer->update([
                        'email' => $email,
                        'name' => $data['name'] ?? $customer->name,
                    ]);
                }
            }

            if (! $customer) {
                $customer = Customer::firstOrCreate(
                    ['email' => $email],
                    $customerDefaults
                );
            }

            // Persist visitor metadata (cart, orders, etc.) sent by the host site.
            if (! empty($data['custom_attributes']) && is_array($data['custom_attributes'])) {
                $existing = $customer->custom_attributes ?? [];
                $customer->update([
                    'custom_attributes' => array_merge($existing, $data['custom_attributes']),
                ]);
            }

            $openStatusIds = Cache::remember(
                'helpdesklivechat:open_status_ids',
                now()->addMinutes(30),
                fn (): array => ConversationStatus::query()->where('is_open', true)->pluck('id')->all()
            );

            $existing = Conversation::where('customer_id', $customer->id)
                ->where('inbox_id', $inbox->id)
                ->whereNull('closed_at')
                ->whereIn('status_id', $openStatusIds)
                ->latest('last_message_at')
                ->first();

            if ($existing) {
                if (! empty($data['message'])) {
                    ConversationItem::create([
                        'conversation_id' => $existing->id,
                        'author_id' => $customer->id,
                        'type' => 'message',
                        'body' => $data['message'],
                        'is_internal' => false,
                    ]);

                    $existing->update([
                        'last_message_at' => now(),
                    ]);
                }

                $this->syncCustomerInbox($customer, $inbox);

                // Recover or backfill the pubsub_token for existing conversations.
                $existingMeta = is_array($existing->metadata)
                    ? $existing->metadata
                    : (json_decode((string) ($existing->metadata ?? '{}'), true) ?? []);
                $existingPubsubToken = $existingMeta['widget_pubsub_token'] ?? null;

                if (! $existingPubsubToken) {
                    $existingPubsubToken = Str::random(32);
                    $existingMeta['widget_pubsub_token'] = $existingPubsubToken;
                    $existing->update(['metadata' => $existingMeta]);
                }

                return [
                    'conversation_id' => (int) $existing->id,
                    'customer_id' => (int) $customer->id,
                    'customer' => [
                        'id' => $customer->id,
                        'email' => $customer->email,
                        'name' => $customer->name,
                    ],
                    'pubsub_token' => $existingPubsubToken,
                    'reused' => true,
                ];
            }

            $openStatus = ConversationStatus::where('is_open', true)
                ->orderBy('order')
                ->first();

            // Generate a cryptographically random pubsub_token stored in metadata.
            // The widget receives this token and appends it to the channel name so
            // that the broadcast channel is unguessable even if the conversation ID
            // is known. No migration needed — metadata is an existing JSON column.
            $pubsubToken = Str::random(32);

            $metadata = ['widget_pubsub_token' => $pubsubToken];
            if (! empty($data['engagement_context']) && is_array($data['engagement_context'])) {
                $metadata['engagement_context'] = $data['engagement_context'];
            }
            // Track widget session token so the agent panel can show technology
            // (IP, browser, OS, country) and visited pages for this conversation.
            // Also link the WidgetSession to the customer so analytics works.
            if (! empty($data['widget_session_token'])) {
                $sessionToken = (string) $data['widget_session_token'];
                $metadata['widget_session_token'] = $sessionToken;

                $req = request();
                $referer = $req->header('Referer') ?? $req->header('Origin') ?? 'https://unknown';

                // Guarantee the session exists (heartbeat may lose the race with conversation creation).
                $this->sessionService->heartbeat($sessionToken, $referer, null, $req);

                WidgetSession::query()
                    ->where('session_token', $sessionToken)
                    ->whereNull('customer_id')
                    ->update(['customer_id' => $customer->id]);
            }

            $conversation = Conversation::create([
                'customer_id' => $customer->id,
                'inbox_id' => $inbox->id,
                'channel' => 'web',
                'status_id' => $openStatus?->id,
                'subject' => Str::limit($data['message'] ?? 'Nueva conversación desde widget', 80, ''),
                'last_message_at' => now(),
                'metadata' => $metadata,
            ]);

            // Cache session→conversation mapping for real-time heartbeat broadcasts.
            if (! empty($data['widget_session_token'])) {
                Cache::put(
                    'helpdesklivechat:session_conv:'.(string) $data['widget_session_token'],
                    $conversation->id,
                    now()->addDay()
                );
            }

            if (! empty($data['message'])) {
                ConversationItem::create([
                    'conversation_id' => $conversation->id,
                    'author_id' => $customer->id,
                    'type' => 'message',
                    'body' => $data['message'],
                    'is_internal' => false,
                ]);
            }

            $this->syncCustomerInbox($customer, $inbox);

            ConversationCreated::dispatch($conversation);

            return [
                'conversation_id' => (int) $conversation->id,
                'customer_id' => (int) $customer->id,
                'customer' => [
                    'id' => $customer->id,
                    'email' => $customer->email,
                    'name' => $customer->name,
                ],
                'pubsub_token' => $pubsubToken,
                'reused' => false,
            ];
        });
    }

    /**
     * Cursor-based pagination for conversation messages.
     *
     * @param  int|null  $beforeId  Fetch messages older than this id (for "load more")
     * @param  int  $limit  Page size, clamped to [1, 200]
     * @return array{conversation_id: int, messages: array<int, array<string, mixed>>, has_more: bool, next_cursor: int|null}
     */
    public function getMessages(int $conversationId, int $customerId, ?int $beforeId = null, int $limit = 100): array
    {
        $conversation = $this->resolveOwnedConversation($conversationId, $customerId);

        $limit = max(1, min(200, $limit));

        $items = ConversationItem::query()
            ->with(['user:id,firstname,lastname', 'author:id,name,email'])
            ->where('conversation_id', $conversation->id)
            ->where('is_internal', false)
            ->where('type', '!=', 'activity')
            ->when($beforeId !== null, fn ($q) => $q->where('id', '<', $beforeId))
            ->orderBy('id', 'desc')
            ->limit($limit + 1)
            ->get();

        $hasMore = $items->count() > $limit;

        if ($hasMore) {
            $items = $items->take($limit);
        }

        // Reverse so the API still returns oldest-first within the page.
        $messages = $items->reverse()
            ->map(fn (ConversationItem $item) => $this->itemToArray($item))
            ->values()
            ->all();

        $nextCursor = $hasMore ? (int) $items->last()->id : null;

        return [
            'conversation_id' => $conversation->id,
            'messages' => $messages,
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
        ];
    }

    /**
     * @param  array<string, mixed>  $data  May include 'content', 'attachments' (array<UploadedFile>).
     * @return array{message_id: int, created_at: string, attachments: array<int, array<string, mixed>>}
     */
    public function sendMessage(int $conversationId, int $customerId, array $data): array
    {
        $conversation = $this->resolveOwnedConversation($conversationId, $customerId);

        // If the visitor has identified themselves (logged in) since the
        // conversation started, update the customer record so the agent sees
        // the real name / email instead of the guest placeholder.
        if (! empty($data['email']) || ! empty($data['name'])) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $update = [];
                if (! empty($data['email']) && $customer->email !== $data['email']) {
                    $update['email'] = $data['email'];
                }
                if (! empty($data['name']) && $customer->name !== $data['name']) {
                    $update['name'] = $data['name'];
                }
                if (! empty($update)) {
                    $customer->update($update);
                }

                // Persist visitor metadata (cart, orders, etc.) sent by the host site.
                if (! empty($data['custom_attributes']) && is_array($data['custom_attributes'])) {
                    $existing = $customer->custom_attributes ?? [];
                    $customer->update([
                        'custom_attributes' => array_merge($existing, $data['custom_attributes']),
                    ]);
                }
            }
        }

        $attachments = $this->storeAttachments($conversationId, $data['attachments'] ?? []);

        // Sanitize visitor-supplied body before persisting. Only applied to web
        // channel messages (widget visitors). Agent messages are NOT sanitized here
        // — agents may send rich HTML via the admin panel using their own editor.
        // clean() uses HTMLPurifier (ezyang/htmlpurifier) which strips dangerous
        // attributes (onclick, javascript:, etc.) — safer than strip_tags allowlist.
        $body = clean((string) ($data['content'] ?? ''));

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $customerId,
            'type' => 'message',
            'body' => $body,
            'is_internal' => false,
            'attachment_urls' => $attachments,
        ]);

        $updates = ['last_message_at' => now()];

        // The visitor is active again — clear any auto-inactive marker so the
        // agent panel no longer shows them as idle (see ProcessAutoActionsCommand).
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        if (! empty($metadata['inactive_since'])) {
            unset($metadata['inactive_since']);
            $updates['metadata'] = $metadata;
        }

        $conversation->update($updates);

        // Broadcast to widget + agent panel + sidebar (single event reaches both).
        event(new MessageReceived($conversation, $item));
        broadcast(new ConversationMessageCreated(
            $item,
            $conversation->wasRecentlyCreated ?? false,
        ));

        return [
            'message_id' => (int) $item->id,
            'created_at' => $item->created_at->toIso8601String(),
            'attachments' => $attachments,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getConversation(int $conversationId, int $customerId): array
    {
        $conversation = $this->resolveOwnedConversation($conversationId, $customerId);
        $conversation->load('customer', 'status');

        return [
            'id' => $conversation->id,
            'subject' => $conversation->subject,
            'created_at' => $conversation->created_at?->toIso8601String(),
            'status' => $conversation->status ? [
                'id' => $conversation->status->id,
                'key' => $conversation->status->key,
                'name' => $conversation->status->name,
                'is_open' => (bool) $conversation->status->is_open,
            ] : null,
            'contact' => $conversation->customer ? [
                'id' => $conversation->customer->id,
                'name' => $conversation->customer->name,
                'email' => $conversation->customer->email,
            ] : null,
            'agent' => null,
        ];
    }

    public function closeConversation(int $conversationId, int $customerId): void
    {
        $conversation = $this->resolveOwnedConversation($conversationId, $customerId);

        $closedStatus = ConversationStatus::where('key', 'closed')
            ->orWhere('is_closed', true)
            ->first();

        $conversation->update([
            'status_id' => $closedStatus?->id ?? $conversation->status_id,
            'closed_at' => now(),
        ]);
    }

    /**
     * Persist uploaded files and return their normalized metadata for the
     * conversation_items.attachment_urls JSON column.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array{name: string, url: string, size: int, mime_type: string}>
     */
    private function storeAttachments(int $conversationId, array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $original = $file->getClientOriginalName();
            $generated = Str::random(16).'_'.preg_replace('/[^A-Za-z0-9._-]+/', '_', $original);
            $path = $file->storeAs("helpdesk-conversations/{$conversationId}", $generated, 'public');

            $stored[] = [
                'name' => $original,
                'url' => Storage::disk('public')->url($path),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            ];
        }

        return $stored;
    }

    /**
     * Map a ConversationItem to the JSON shape consumed by the widget.
     *
     * @return array<string, mixed>
     */
    private function itemToArray(ConversationItem $item): array
    {
        $isAgent = ! is_null($item->user_id);
        $contentType = 'text';
        $attachments = is_array($item->attachment_urls) ? $item->attachment_urls : [];

        if (count($attachments) > 0) {
            $first = $attachments[0];
            $mime = is_array($first) ? ($first['mime_type'] ?? '') : '';
            $contentType = match (true) {
                str_starts_with($mime, 'image/') => 'image',
                str_starts_with($mime, 'audio/') => 'audio',
                str_starts_with($mime, 'video/') => 'video',
                default => 'file',
            };
        }

        // Link preview is only emitted to the widget when the agent is the sender —
        // visitor URLs are NOT auto-unfurled.
        $linkPreview = $isAgent ? ($item->metadata['link_preview'] ?? null) : null;

        return [
            'id' => $item->id,
            'content' => $item->body,
            'content_type' => $contentType,
            'message_type' => $isAgent ? 'outgoing' : 'incoming',
            'created_at' => $item->created_at->toIso8601String(),
            'sender' => [
                'id' => $isAgent ? $item->user_id : $item->author_id,
                'type' => $isAgent ? 'User' : 'Customer',
                'name' => $this->resolveSenderName($item),
            ],
            'attachments' => $this->normalizeAttachments($attachments),
            'link_preview' => $linkPreview,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, url: string, size: int, mime_type: string}>
     */
    private function normalizeAttachments(array $attachments): array
    {
        $out = [];
        foreach ($attachments as $i => $a) {
            if (is_string($a)) {
                $out[] = [
                    'id' => $i,
                    'name' => basename($a),
                    'url' => $a,
                    'size' => 0,
                    'mime_type' => '',
                ];

                continue;
            }
            $out[] = [
                'id' => $i,
                'name' => (string) ($a['name'] ?? basename($a['url'] ?? '')),
                'url' => (string) ($a['url'] ?? ''),
                'size' => (int) ($a['size'] ?? 0),
                'mime_type' => (string) ($a['mime_type'] ?? ''),
            ];
        }

        return $out;
    }

    private function resolveSenderName(ConversationItem $item): string
    {
        if ($item->user_id) {
            $name = optional($item->user)->name ?? 'Agent';

            return is_string($name) ? $name : 'Agent';
        }

        return optional($item->author)->name ?? 'Visitor';
    }

    private function resolveOwnedConversation(int $conversationId, int $customerId): Conversation
    {
        $conversation = Conversation::find($conversationId);
        if (! $conversation) {
            throw new \RuntimeException('Conversation not found');
        }
        if ((int) $conversation->customer_id !== (int) $customerId) {
            throw new \RuntimeException('Unauthorized access to conversation');
        }

        return $conversation;
    }

    private function syncCustomerInbox(Customer $customer, Inbox $inbox): void
    {
        $customer->inboxes()->syncWithoutDetaching([
            $inbox->id => [
                'source_id' => $customer->email,
                'last_seen_at' => now(),
            ],
        ]);
    }
}
