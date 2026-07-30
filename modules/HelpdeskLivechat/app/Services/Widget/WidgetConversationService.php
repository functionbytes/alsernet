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
    /**
     * Ids de estados "abiertos" cacheados para la reutilización de
     * conversaciones del widget. Se invalida al crear/editar/borrar
     * ConversationStatus (ver HelpdeskLivechatServiceProvider).
     */
    public const OPEN_STATUS_IDS_CACHE_KEY = 'helpdesklivechat:open_status_ids';

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

        $result = DB::connection('helpdesk')->transaction(function () use ($web, $data) {
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

            // La identidad se resuelve desde la sesión del widget del lado del
            // SERVIDOR (WidgetSession.customer_id), NUNCA desde el customer_id
            // que envía el cliente: confiar en ese id permitía a cualquier
            // visitante adjuntar su chat a —y sobrescribir el email/nombre de—
            // cualquier cliente por id (impersonación + toma de datos).
            $session = ! empty($data['widget_session_token'])
                ? WidgetSession::where('session_token', $data['widget_session_token'])->first()
                : null;

            if ($session && $session->customer_id) {
                $customer = Customer::find($session->customer_id);
            }

            if (! $customer) {
                $customer = Customer::firstOrCreate(
                    ['email' => $email],
                    $customerDefaults
                );
            }

            // Vincula el cliente a la sesión para dar continuidad segura en las
            // siguientes conversaciones del mismo visitante (id en el servidor).
            if ($session && (int) $session->customer_id !== (int) $customer->id) {
                $session->update(['customer_id' => $customer->id]);
            }

            // Promociona el email placeholder de invitado al real cuando el
            // visitante se identifica — solo sobre el cliente de SU sesión,
            // nunca uno ajeno, y sin machacar un email ya real.
            if (! empty($data['email']) && str_ends_with((string) $customer->email, '@anonymous.local')) {
                $customer->update([
                    'email' => $data['email'],
                    'name' => $data['name'] ?? $customer->name,
                ]);
            }

            // Persist visitor metadata (cart, orders, etc.) sent by the host site.
            if (! empty($data['custom_attributes']) && is_array($data['custom_attributes'])) {
                $existing = $customer->custom_attributes ?? [];
                $customer->update([
                    'custom_attributes' => array_merge($existing, $data['custom_attributes']),
                ]);
            }

            $openStatusIds = Cache::remember(
                self::OPEN_STATUS_IDS_CACHE_KEY,
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
                $firstMessageId = null;
                if (! empty($data['message'])) {
                    $firstItem = ConversationItem::create([
                        'conversation_id' => $existing->id,
                        'author_id' => $customer->id,
                        'type' => 'message',
                        'body' => $data['message'],
                        'is_internal' => false,
                    ]);
                    $firstMessageId = (int) $firstItem->id;

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
                    'message_id' => $firstMessageId,
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
            // The heartbeat that guarantees the session row exists (and the
            // customer link that depends on it) runs AFTER the transaction —
            // see below — so a synchronous session upsert doesn't extend the
            // lifetime of this write transaction.
            if (! empty($data['widget_session_token'])) {
                $metadata['widget_session_token'] = (string) $data['widget_session_token'];
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

            $firstMessageId = null;
            if (! empty($data['message'])) {
                $firstItem = ConversationItem::create([
                    'conversation_id' => $conversation->id,
                    'author_id' => $customer->id,
                    'type' => 'message',
                    'body' => $data['message'],
                    'is_internal' => false,
                ]);
                $firstMessageId = (int) $firstItem->id;
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
                'message_id' => $firstMessageId,
                'reused' => false,
            ];
        });

        // Post-commit work: guarantee the WidgetSession row exists (the widget
        // heartbeat may lose the race with conversation creation) and link it
        // to the resolved customer so analytics and the agent panel work.
        // Kept OUT of the transaction above: heartbeat() is a synchronous
        // upsert (plus optional geo job dispatch) that used to run inside the
        // write transaction and needlessly extended its lock lifetime.
        if (! $result['reused'] && ! empty($data['widget_session_token'])) {
            $sessionToken = (string) $data['widget_session_token'];
            $req = request();
            $referer = $req->header('Referer') ?? $req->header('Origin') ?? 'https://unknown';

            $this->sessionService->heartbeat($sessionToken, $referer, null, $req);

            WidgetSession::query()
                ->where('session_token', $sessionToken)
                ->whereNull('customer_id')
                ->update(['customer_id' => $result['customer_id']]);
        }

        return $result;
    }

    /**
     * Cursor-based pagination for conversation messages.
     *
     * Recibe la Conversation YA resuelta y autorizada por el controller
     * (authorizeConversation + X-Conversation-Token) para no recargarla aquí.
     *
     * @param  int|null  $beforeId  Fetch messages older than this id (for "load more")
     * @param  int  $limit  Page size, clamped to [1, 200]
     * @return array{conversation_id: int, messages: array<int, array<string, mixed>>, has_more: bool, next_cursor: int|null}
     */
    public function getMessages(Conversation $conversation, int $customerId, ?int $beforeId = null, int $limit = 100): array
    {
        $this->assertOwnedByCustomer($conversation, $customerId);

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
    public function sendMessage(Conversation $conversation, int $customerId, array $data): array
    {
        $this->assertOwnedByCustomer($conversation, $customerId);

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

        $attachments = $this->storeAttachments($conversation->id, $data['attachments'] ?? []);

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
    public function getConversation(Conversation $conversation, int $customerId): array
    {
        $this->assertOwnedByCustomer($conversation, $customerId);
        $conversation->load('customer', 'status', 'assignee');

        // Only non-sensitive assignee data crosses to the (public) widget:
        // display name and avatar, never email/phone/roles.
        $assignee = $conversation->assignee;

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
            'agent' => $assignee ? [
                'id' => $assignee->id,
                'name' => trim(($assignee->firstname ?? '').' '.($assignee->lastname ?? '')) ?: 'Agente',
                'avatar' => method_exists($assignee, 'getAvatarUrl') ? $assignee->getAvatarUrl() : null,
            ] : null,
        ];
    }

    public function closeConversation(Conversation $conversation, int $customerId): void
    {
        $this->assertOwnedByCustomer($conversation, $customerId);

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
        // Carrusel de productos (coviewer): mensaje saliente (lo muestra la
        // tienda/agente/bot), con los productos en metadata.products.
        if ($item->type === 'product_carousel') {
            $metadata = is_array($item->metadata) ? $item->metadata : [];

            return [
                'id' => $item->id,
                'content' => $item->body,
                'content_type' => 'products',
                'message_type' => 'outgoing',
                'created_at' => $item->created_at->toIso8601String(),
                'sender' => [
                    'id' => $item->user_id,
                    'type' => ! empty($metadata['is_bot']) ? 'Bot' : 'User',
                    'name' => ! empty($metadata['is_bot']) ? 'Asistente' : $this->resolveSenderName($item),
                ],
                'products' => is_array($metadata['products'] ?? null) ? $metadata['products'] : [],
                'attachments' => [],
                'link_preview' => null,
            ];
        }

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

    /**
     * Defensa en profundidad: el controller ya resolvió y autorizó la
     * conversación (token secreto por cabecera). Aquí solo se re-valida la
     * propiedad sobre la instancia recibida, sin recargarla de la BD (antes se
     * hacía un Conversation::find duplicado en cada request del widget).
     */
    private function assertOwnedByCustomer(Conversation $conversation, int $customerId): void
    {
        if ((int) $conversation->customer_id !== $customerId) {
            throw new \RuntimeException('Unauthorized access to conversation');
        }
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
