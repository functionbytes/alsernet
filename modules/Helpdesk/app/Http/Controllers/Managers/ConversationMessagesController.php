<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\ConversationMessageRead;
use Modules\Helpdesk\Events\ConversationUserTyping;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Http\Requests\BroadcastTypingRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationMessageRequest;
use Modules\Helpdesk\Jobs\SendOutboundMessageJob;
use Modules\Helpdesk\Models\CannedReply;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationRead;
use Modules\Helpdesk\Services\Conversations\ConversationInboxMetricsService;
use Modules\Helpdesk\Services\OutboundMessageService;

class ConversationMessagesController extends Controller
{
    public function __construct(
        private readonly OutboundMessageService $outbound,
        private readonly ConversationInboxMetricsService $inboxMetrics,
    ) {}

    /**
     * Get all messages for a conversation (API endpoint)
     */
    public function index(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $items = $conversation->items()
            ->with(['author', 'user'])
            ->latest()
            ->paginate(50);

        return response()->json($items);
    }

    /**
     * Store a new message in a conversation
     */
    public function store(StoreConversationMessageRequest $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();

        // Handle attachments — store with full metadata (matches widget format)
        // so the thread renderer always has {url, name, size, mime_type, type}.
        $attachmentUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('helpdesk/attachments', 'public');
                $mime = $file->getMimeType() ?? 'application/octet-stream';
                $attachmentUrls[] = [
                    'url' => Storage::url($path),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $mime,
                    'type' => $this->mediaTypeFromMime($mime),
                    'path' => $path,
                ];
            }
        }

        // Link preview (OG metadata) for URLs in the agent's body — shown in the
        // visitor widget (and this panel) like a WhatsApp/Instagram link unfurl.
        // Fetched OFF the request thread by GenerateLinkPreviewJob (dispatched
        // from ConversationItemLinkPreviewObserver::created() below) — a
        // synchronous fetch here (up to 6s / 2MB) used to block every message
        // with a URL, and duplicated the fetch the observer's job was already
        // going to make.
        $item = $conversation->items()->create([
            'type' => 'message',
            'body' => $validated['body'],
            // Derivado del body escapado: evita XSS almacenado y el acceso a una
            // clave 'html_body' inexistente en el FormRequest validado.
            'html_body' => filled($validated['body'] ?? null) ? nl2br(e($validated['body'])) : null,
            'user_id' => auth()->id(),
            'is_internal' => $validated['is_internal'] ?? false,
            'attachment_urls' => $attachmentUrls,
        ]);

        $item->load(['user']);

        $conversation->update(['last_message_at' => now()]);

        if ($item->is_internal === false && ! $conversation->first_response_at) {
            $conversation->update(['first_response_at' => now()]);
        }

        // Send to the customer via the channel API (Facebook/Instagram/WhatsApp)
        // off the request thread: the Graph/WhatsApp clients use 15s timeouts with
        // retries and would otherwise hold a PHP-FPM worker for the whole call.
        // Text first, then each attachment as a separate API call (Meta requires
        // one attachment per message). The external id is correlated back onto the
        // item inside the job once it returns.
        if (! $item->is_internal && $this->outbound->supports($conversation)) {
            $outboundAttachments = array_map(fn ($att) => [
                'type' => $att['type'] ?? 'file',
                'url' => $this->absoluteUrl((string) $att['url']),
                'name' => $att['name'] ?? null,
            ], $attachmentUrls);

            $bodyText = (string) $item->body;

            // La auto-traducción saliente (si aplica) se resuelve DENTRO del
            // job, no aquí — ver SendOutboundMessageJob::resolveOutboundBody().
            // Resolverla en el hilo HTTP repetía exactamente el bloqueo de
            // FPM que este job existe para evitar (DeepL: timeout 15s ×2
            // reintentos, más el fallback a LibreTranslate si falla).
            SendOutboundMessageJob::dispatch(
                $conversation->id,
                $item->id,
                filled($bodyText) ? $bodyText : null,
                $outboundAttachments,
            );
        }

        // Single broadcast reaches both the open thread channel and the global
        // inbox channel — sidebar updates without a second round-trip.
        broadcast(new ConversationMessageCreated($item, false))->toOthers();

        // NOTE: el preview OpenGraph (si el body trae una URL) lo genera
        // GenerateLinkPreviewJob FUERA del hilo HTTP (fetch de hasta 6s) —
        // antes había aquí un fetch síncrono "belt-and-suspenders" que
        // reintroducía exactamente ese bloqueo en cada mensaje con URL,
        // duplicando además la descarga que el job ya iba a hacer. Al
        // terminar, el job re-emite MessageReceived (widget) Y
        // ConversationMessageCreated (este panel), y ambos lados reemplazan
        // la burbuja existente por id en vez de duplicarla — el agente ve el
        // mensaje al instante y la tarjeta de preview aparece un instante
        // después, en vez de esperar hasta 6s por ella.
        $reloaded = ConversationItem::with('user')->find($item->id);
        if ($reloaded) {
            $item = $reloaded;
        }

        $payload = [
            'id' => $item->id,
            'conversation_id' => $conversation->id,
            'user_id' => $item->user_id,
            'type' => $item->type,
            'body' => $item->body,
            'html_body' => $item->html_body,
            'is_internal' => $item->is_internal,
            'created_at' => $item->created_at,
            'time' => $item->created_at?->format('H:i'),
            'author' => $item->user->name ?? 'Tú',
            'sender_name' => $item->user->name ?? 'Unknown',
            'sender_avatar' => $item->user?->getAvatarUrl(),
            'attachment_urls' => $item->attachment_urls,
            'metadata' => $item->metadata,
            'is_incoming' => false,
        ];

        // Returned in two shapes for backward compatibility:
        //   - flat: legacy callers reading top-level fields
        //   - .item: the conversations composer expects `resp.item`
        return response()->json(array_merge($payload, ['item' => $payload]), 201);
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(Request $request, ConversationItem $item)
    {
        $item->loadMissing('conversation');
        $this->authorize('view', $item->conversation);

        // Create or update read record
        ConversationRead::firstOrCreate([
            'conversation_item_id' => $item->id,
            'user_id' => auth()->id(),
        ]);

        // Otherwise the sidebar's "Sin leer" badge keeps showing the stale
        // pre-read count for up to 120s (Cache::flexible TTL) after opening
        // a conversation marks it read.
        $this->inboxMetrics->forgetCountersFor(auth()->id());

        // Broadcast event
        broadcast(new ConversationMessageRead($item, auth()->user()))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all customer messages in a conversation as read AND notify the
     * customer's external channel (Facebook/Instagram seen indicator).
     */
    public function markConversationRead(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $userId = auth()->id();

        // Mark the whole conversation as read for this user (idempotent).
        // The reads table is keyed by (conversation_id, user_id), not per-item.
        ConversationRead::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $userId],
            ['read_at' => now()],
        );

        // Otherwise the sidebar's "Sin leer" badge keeps showing the stale
        // pre-read count for up to 120s (Cache::flexible TTL) after opening
        // a conversation marks it read.
        $this->inboxMetrics->forgetCountersFor($userId);

        // Send "seen" receipt to the customer via the channel API.
        if ($this->outbound->supports($conversation)) {
            $latestItem = $conversation->items()
                ->whereNotNull('author_id')
                ->whereNull('user_id')
                ->latest('id')
                ->first(['id', 'external_id']);

            $this->outbound->markSeen($conversation, $latestItem?->external_id);
        }

        return response()->json(['success' => true]);
    }

    private function mediaTypeFromMime(string $mime): string
    {
        $primary = strtolower(explode('/', explode(';', $mime, 2)[0], 2)[0] ?? '');

        return match ($primary) {
            'image' => 'image',
            'audio' => 'audio',
            'video' => 'video',
            default => 'document',
        };
    }

    /**
     * Convert a relative URL like "/storage/..." into an absolute https URL
     * that Meta can fetch when delivering attachments.
     */
    private function absoluteUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        // Prefer the public webhook host if configured, otherwise fall back to APP_URL.
        $base = rtrim(config('helpdesk.public_url') ?? config('app.url'), '/');

        return $base.'/'.ltrim($url, '/');
    }

    /**
     * Broadcast typing indicator to other agents AND to the customer's channel
     * (Facebook/Instagram show "Typing..." live).
     */
    public function broadcastTyping(BroadcastTypingRequest $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $validated = $request->validated();
        $isTyping = (bool) $validated['is_typing'];

        broadcast(new ConversationUserTyping(
            $conversation,
            auth()->user(),
            $isTyping,
        ))->toOthers();

        // Forward typing state to the customer's channel for FB/IG.
        if ($this->outbound->supports($conversation)) {
            $this->outbound->setTyping($conversation, $isTyping);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete a message (soft delete)
     */
    public function destroy(Request $request, ConversationItem $item)
    {
        $item->loadMissing('conversation');
        $this->authorize('update', $item->conversation);

        // Only allow deleting own messages or if admin
        if ($item->user_id !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get canned replies for current user
     */
    public function getCannedReplies()
    {
        $replies = CannedReply::query()
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhere('is_global', true);
            })
            ->latest('usage_count')
            ->get(['id', 'title', 'body', 'html_body']);

        return response()->json($replies);
    }
}
