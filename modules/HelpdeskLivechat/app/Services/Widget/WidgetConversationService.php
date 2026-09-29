<?php

namespace Modules\HelpdeskLivechat\Services\Widget;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Http\Controllers\Pages\WidgetAttachmentController;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Services\WidgetSessionService;
use Modules\HelpdeskLivechat\Support\VisitorMessageSanitizer;

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
    public function createConversation(string $websiteToken, array $data, ?string $verifiedIdentifier = null): array
    {
        $web = Web::where('website_token', $websiteToken)->first();
        if (! $web) {
            throw new \RuntimeException('Invalid widget token');
        }

        $result = DB::connection('helpdesk')->transaction(function () use ($web, $data, $verifiedIdentifier) {
            $inbox = Inbox::firstOrCreate(
                ['channel_type' => 'web', 'channel_id' => $web->id],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => $web->name ?? 'Widget Web',
                    'is_active' => true,
                ]
            );

            // Identidad verificada: la tienda firma el email del cliente logueado
            // con el hmac_token del canal (identifier_hash). Solo entonces el
            // email está probado y manda sobre lo que diga la sesión.
            $verifiedEmail = $this->verifiedIdentityEmail($web, $data);

            $claimedEmail = $verifiedEmail !== null
                ? mb_strtolower(trim($verifiedEmail))
                : (! empty($data['email']) ? mb_strtolower(trim((string) $data['email'])) : null);

            // El email lo escribe el visitante: solo cuenta como verificado si lo
            // firmó la tienda o si VerifyWidgetHmac validó el HMAC sobre ESTE email.
            $identityVerified = $claimedEmail !== null && (
                $verifiedEmail !== null
                || ($verifiedIdentifier !== null && strcasecmp(trim($verifiedIdentifier), $claimedEmail) === 0)
            );

            $customerDefaults = [
                'name' => $data['name'] ?? 'Anonymous',
                'phone' => $data['phone_number'] ?? null,
                'language' => $data['language'] ?? 'es',
            ];

            $customer = $verifiedEmail !== null
                ? Customer::firstOrCreate(['email' => $verifiedEmail], $customerDefaults)
                : null;
            // true cuando el cliente se ha resuelto por un email no verificado que
            // pertenece a otra ficha: se crea una ficha de invitado aparte.
            $unverifiedClaim = false;

            // La identidad se resuelve desde la sesión del widget del lado del
            // SERVIDOR (WidgetSession.customer_id), NUNCA desde el customer_id
            // que envía el cliente: confiar en ese id permitía a cualquier
            // visitante adjuntar su chat a —y sobrescribir el email/nombre de—
            // cualquier cliente por id (impersonación + toma de datos).
            $session = ! empty($data['widget_session_token'])
                ? WidgetSession::where('session_token', $data['widget_session_token'])->first()
                : null;

            if (! $customer && $session && $session->customer_id) {
                $customer = Customer::find($session->customer_id);
            }

            // 29-sep-2026 (A5): antes firstOrCreate(['email' => ...]) adjuntaba el
            // chat a la ficha REAL del cliente cuyo email escribiera cualquiera y
            // devolvía el token de su conversación abierta. Un email no verificado
            // nunca da acceso a una ficha existente: se crea una de invitado.
            if (! $customer) {
                $existingCustomer = $claimedEmail !== null
                    ? Customer::where('email', $claimedEmail)->first()
                    : null;

                if ($existingCustomer && $identityVerified) {
                    $customer = $existingCustomer;
                } elseif ($existingCustomer) {
                    $unverifiedClaim = true;
                    $customer = Customer::create($customerDefaults + [
                        'email' => 'guest-'.Str::random(12).'@anonymous.local',
                        'custom_attributes' => [
                            'claimed_email' => $claimedEmail,
                            'email_verified' => false,
                        ],
                    ]);
                } else {
                    $customer = Customer::create($customerDefaults + [
                        'email' => $claimedEmail ?? 'guest-'.Str::random(12).'@anonymous.local',
                    ]);
                }
            }

            // Vincula el cliente a la sesión para dar continuidad segura en las
            // siguientes conversaciones del mismo visitante (id en el servidor).
            if ($session && (int) $session->customer_id !== (int) $customer->id) {
                $session->update(['customer_id' => $customer->id]);
            }

            // Promociona el email placeholder de invitado al real cuando el
            // visitante se identifica — solo sobre el cliente de SU sesión,
            // nunca uno ajeno, sin machacar un email ya real y sin tomar un
            // email que ya pertenece a otra ficha (se anota como reclamado).
            if ($claimedEmail !== null && ! $unverifiedClaim && str_ends_with((string) $customer->email, '@anonymous.local')) {
                $taken = Customer::where('email', $claimedEmail)
                    ->whereKeyNot($customer->id)
                    ->exists();

                if (! $taken) {
                    $customer->update([
                        'email' => $claimedEmail,
                        'name' => $data['name'] ?? $customer->name,
                    ]);
                } else {
                    $customer->update([
                        'custom_attributes' => array_merge($customer->custom_attributes ?? [], [
                            'claimed_email' => $claimedEmail,
                            'email_verified' => false,
                        ]),
                    ]);
                }
            }

            // Persist visitor metadata (cart, orders, etc.) sent by the host site.
            if (! empty($data['custom_attributes']) && is_array($data['custom_attributes'])) {
                $existing = $customer->custom_attributes ?? [];
                $customer->update([
                    'custom_attributes' => array_merge($existing, $this->visitorAttributes($data['custom_attributes'])),
                ]);
            }

            $openStatusIds = Cache::remember(
                self::OPEN_STATUS_IDS_CACHE_KEY,
                now()->addMinutes(30),
                fn (): array => ConversationStatus::query()->where('is_open', true)->pluck('id')->all()
            );

            // 29-sep-2026 (A5): solo se reutiliza (y se devuelve su pubsub_token)
            // una conversación abierta que creó ESTA misma sesión del widget
            // (metadata.widget_session_token). Antes bastaba con resolver el
            // cliente, lo que entregaba el token del chat de otra persona.
            $sessionToken = (string) ($data['widget_session_token'] ?? '');
            $existing = $sessionToken === '' ? null : Conversation::where('customer_id', $customer->id)
                ->where('inbox_id', $inbox->id)
                ->whereNull('closed_at')
                ->whereIn('status_id', $openStatusIds)
                ->latest('last_message_at')
                ->limit(20)
                ->get()
                ->first(function (Conversation $c) use ($sessionToken): bool {
                    $meta = is_array($c->metadata) ? $c->metadata : [];

                    return is_string($meta['widget_session_token'] ?? null)
                        && hash_equals($meta['widget_session_token'], $sessionToken);
                });

            if ($existing) {
                $firstMessageId = null;
                if (! empty($data['message'])) {
                    $firstItem = ConversationItem::create([
                        'conversation_id' => $existing->id,
                        'author_id' => $customer->id,
                        'type' => 'message',
                        'body' => $this->plainText((string) $data['message']),
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
                    'customer' => ['id' => $customer->id],
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
            if ($verifiedEmail !== null) {
                $metadata['identity_verified'] = true;
            }
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
                // Sin etiquetas ni < >: el asunto sale del texto del visitante y se
                // pinta en listados y notificaciones del panel (A6, 29-sep-2026).
                'subject' => Str::limit(trim(str_replace(['<', '>'], '', strip_tags((string) ($data['message'] ?? ''))))
                    ?: 'Nueva conversación desde widget', 80, ''),
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
                    'body' => $this->plainText((string) $data['message']),
                    'is_internal' => false,
                ]);
                $firstMessageId = (int) $firstItem->id;
            }

            $this->syncCustomerInbox($customer, $inbox);

            ConversationCreated::dispatch($conversation);

            return [
                'conversation_id' => (int) $conversation->id,
                'customer_id' => (int) $customer->id,
                // Sin email/nombre: la respuesta es pública (no enumerar clientes).
                'customer' => ['id' => $customer->id],
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

            // Solo si el widget aún no la creó: el Referer de una petición
            // entre orígenes suele traer solo el dominio, y usarlo sobre una
            // sesión existente pisaba la URL y el producto reales del visitante.
            if (! WidgetSession::where('session_token', $sessionToken)->exists()) {
                $req = request();
                $referer = $req->header('Referer') ?? $req->header('Origin') ?? 'https://unknown';

                $this->sessionService->heartbeat($sessionToken, $referer, null, $req);
            }

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
                // 29-sep-2026: el visitante solo puede fijar su email si aún es un
                // invitado (@anonymous.local) y el email no es de otra ficha;
                // antes podía poner cualquier email libre sobre una ficha real.
                $newEmail = ! empty($data['email']) ? mb_strtolower(trim((string) $data['email'])) : null;
                if ($newEmail !== null
                    && strcasecmp((string) $customer->email, $newEmail) !== 0
                    && str_ends_with((string) $customer->email, '@anonymous.local')
                    && ! Customer::where('email', $newEmail)->whereKeyNot($customer->id)->exists()) {
                    $update['email'] = $newEmail;
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
                        'custom_attributes' => array_merge($existing, $this->visitorAttributes($data['custom_attributes'])),
                    ]);
                }
            }
        }

        $attachments = $this->storeAttachments($conversation->id, $data['attachments'] ?? []);

        // Sanitize visitor-supplied body before persisting. Only applied to web
        // channel messages (widget visitors). Agent messages are NOT sanitized here
        // — agents may send rich HTML via the admin panel using their own editor.
        // HTMLPurifier (ezyang/htmlpurifier) quita toda etiqueta: el visitante
        // envía texto plano — más seguro que un allowlist de strip_tags.
        $body = VisitorMessageSanitizer::clean((string) ($data['content'] ?? ''));

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

        // Broadcast to agent panel + sidebar. MessageReceived (widget channel +
        // "Nuevo mensaje del cliente" notification) is NOT dispatched here —
        // ConversationItem::create() above already triggers it via
        // ConversationItemLinkPreviewObserver, the single source of truth (see
        // its docblock). Dispatching it here too doubled every notification
        // sent to the agent for every widget message (bug: a burst of N
        // customer messages piled up 2N identical toasts).
        broadcast(new ConversationMessageCreated(
            $item,
            $conversation->wasRecentlyCreated ?? false,
        ));

        return [
            'message_id' => (int) $item->id,
            'created_at' => $item->created_at->toIso8601String(),
            'attachments' => array_map(fn (array $a): array => $this->visitorAttachment($a), $attachments),
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
                'name' => $assignee->fullName() ?: 'Agente',
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
            // 29-sep-2026 (A6-c): disco privado y nombre generado por el servidor
            // (hashName + extensión según el contenido). El nombre original solo
            // se guarda para mostrarlo. Se sirve por WidgetAttachmentController.
            $original = Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 200, '');
            $path = $file->store("helpdesk-conversations/{$conversationId}", 'local');
            if ($path === false) {
                throw new \RuntimeException('Attachment could not be stored');
            }

            $stored[] = [
                'name' => $original,
                'url' => route(WidgetAttachmentController::ROUTE_NAME, [
                    'conversation' => $conversationId,
                    'file' => basename($path),
                ]),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'disk' => 'local',
                'path' => $path,
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
            $isBot = $item->isFromBot();

            return [
                'id' => $item->id,
                'content' => $item->body,
                'content_type' => 'products',
                'message_type' => 'outgoing',
                'created_at' => $item->created_at->toIso8601String(),
                'sender' => [
                    'id' => $item->user_id,
                    'type' => $isBot ? 'Bot' : 'User',
                    'name' => $isBot ? 'Asistente' : $this->resolveSenderName($item),
                ],
                'products' => is_array($metadata['products'] ?? null) ? $metadata['products'] : [],
                'attachments' => [],
                'link_preview' => null,
                'is_bot' => $isBot,
                ...$this->botExtras($metadata),
            ];
        }

        $isAgent = ! is_null($item->user_id);
        $isBot = $item->isFromBot();
        $isOutgoing = $isAgent || $isBot;
        $contentType = 'text';
        $attachments = is_array($item->attachment_urls) ? $item->attachment_urls : [];
        $metadata = is_array($item->metadata) ? $item->metadata : [];

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

        // Link preview is only emitted to the widget when the sender is not the
        // visitor — visitor URLs are NOT auto-unfurled.
        $linkPreview = $isOutgoing ? ($metadata['link_preview'] ?? null) : null;

        return [
            'id' => $item->id,
            'content' => $item->body,
            'content_type' => $contentType,
            'message_type' => $isOutgoing ? 'outgoing' : 'incoming',
            'created_at' => $item->created_at->toIso8601String(),
            'sender' => [
                'id' => $isAgent ? $item->user_id : $item->author_id,
                'type' => $isBot ? 'Bot' : ($isAgent ? 'User' : 'Customer'),
                'name' => $this->resolveSenderName($item),
            ],
            'attachments' => $this->normalizeAttachments($attachments),
            'link_preview' => $linkPreview,
            'is_bot' => $isBot,
            ...$this->botExtras($metadata),
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
            $a = $this->visitorAttachment($a);
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

    /**
     * Bot-only extras the widget renders as interactive UI: quick-reply/CSAT
     * buttons (options + the prompt they answer, from quick_replies/csat/
     * rich_message nodes) and product/rich-message cards (rich_message with
     * one or several cards). Empty/null for non-bot items or plain text.
     *
     * @param  array<string, mixed>  $metadata
     * @return array{options: array<int, string>, prompt: string|null, cards: array<int, array{title: string, subtitle: string, image_url: mixed, url: mixed}>}
     */
    private function botExtras(array $metadata): array
    {
        $options = is_array($metadata['bot_options'] ?? null) ? array_values($metadata['bot_options']) : [];
        $prompt = isset($metadata['bot_prompt']) ? (string) $metadata['bot_prompt'] : null;

        $rawCards = is_array($metadata['cards'] ?? null)
            ? $metadata['cards']
            : (is_array($metadata['card'] ?? null) ? [$metadata['card']] : []);

        $cards = array_values(array_map(fn (array $c): array => [
            'title' => (string) ($c['title'] ?? ''),
            'subtitle' => (string) ($c['subtitle'] ?? ''),
            'image_url' => $c['image_url'] ?? null,
            'url' => $c['url'] ?? null,
        ], array_filter($rawCards, 'is_array')));

        return ['options' => $options, 'prompt' => $prompt, 'cards' => $cards];
    }

    /**
     * Para el visitante (sin sesión) los adjuntos del disco privado se
     * entregan con una URL firmada y temporal; la URL guardada es la del panel.
     *
     * @param  array<string, mixed>  $attachment
     * @return array<string, mixed>
     */
    private function visitorAttachment(array $attachment): array
    {
        $path = (string) ($attachment['path'] ?? '');
        if (($attachment['disk'] ?? null) === 'local'
            && preg_match('#^helpdesk-conversations/(\d+)/([A-Za-z0-9]{40}(?:\.[a-z0-9]{1,5})?)$#', $path, $m)) {
            $attachment['url'] = URL::temporarySignedRoute(
                WidgetAttachmentController::ROUTE_NAME,
                now()->addHours(12),
                ['conversation' => (int) $m[1], 'file' => $m[2]],
            );
        }
        unset($attachment['disk'], $attachment['path']);

        return $attachment;
    }

    /**
     * Texto del visitante sin etiquetas HTML ni comentarios. A diferencia de
     * strip_tags() conserva texto como "<3" o "a < b" (no son etiquetas); un
     * "<" que pudiera abrir una etiqueta sin cerrar se neutraliza con un espacio.
     */
    private function plainText(string $text): string
    {
        $text = (string) preg_replace('#<!--.*?-->|</?[a-zA-Z][^>]*>#s', '', $text);

        return trim((string) preg_replace('#<(?=[a-zA-Z/!?])#', '< ', $text));
    }

    private function resolveSenderName(ConversationItem $item): string
    {
        if ($item->isFromBot()) {
            return 'Asistente';
        }

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

    /**
     * Atributos que manda el visitante, sin las claves que marca el servidor
     * (un visitante no puede autoproclamarse "email verificado").
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function visitorAttributes(array $attributes): array
    {
        unset($attributes['claimed_email'], $attributes['email_verified']);

        return $attributes;
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

    /**
     * Email del cliente si la tienda lo firmó con el hmac_token del canal
     * (identifier + identifier_hash, HMAC-SHA256). null si no viene firmado
     * o la firma no cuadra: entonces el email es solo declarado por el
     * visitante y no se usa para elegir el cliente por encima de la sesión.
     *
     * @param  array<string, mixed>  $data
     */
    private function verifiedIdentityEmail(Web $web, array $data): ?string
    {
        $identifier = $data['identifier'] ?? null;
        $hash = $data['identifier_hash'] ?? null;
        $secret = (string) ($web->hmac_token ?? '');

        if (! is_string($identifier) || ! is_string($hash) || $secret === ''
            || filter_var($identifier, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return hash_equals(hash_hmac('sha256', $identifier, $secret), $hash) ? $identifier : null;
    }
}
