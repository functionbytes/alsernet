<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationRead;
use Modules\Helpdesk\Models\CsatRating;
use Modules\HelpdeskLivechat\Concerns\VerifiesConversationToken;
use Modules\HelpdeskLivechat\Events\WidgetTyping;
use Modules\HelpdeskLivechat\Http\Requests\Widget\CloseWidgetConversationRequest;
use Modules\HelpdeskLivechat\Http\Requests\Widget\EmailTranscriptRequest;
use Modules\HelpdeskLivechat\Http\Requests\Widget\MarkAsReadRequest;
use Modules\HelpdeskLivechat\Http\Requests\Widget\SendWidgetMessageRequest;
use Modules\HelpdeskLivechat\Http\Requests\Widget\StoreWidgetConversationRequest;
use Modules\HelpdeskLivechat\Mail\ConversationTranscriptMail;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Services\Widget\WidgetConversationService;

class WidgetConversationController extends Controller
{
    use VerifiesConversationToken;

    public function __construct(
        private readonly WidgetConversationService $service
    ) {}

    public function store(StoreWidgetConversationRequest $request): JsonResponse
    {
        try {
            $data = $this->service->createConversation(
                $request->validated('website_token'),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Conversation created successfully',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to create conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $conversation = $this->authorizeConversation($request, $id);

        if (! $conversation) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = $this->service->getConversation($conversation->id, (int) $conversation->customer_id);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $this->httpStatusForServiceException($e));
        }
    }

    public function getMessages(Request $request, string $id): JsonResponse
    {
        $conversation = $this->authorizeConversation($request, $id);

        if (! $conversation) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $beforeId = $request->filled('before_id') ? (int) $request->input('before_id') : null;
            $limit = (int) $request->input('limit', 100);

            $data = $this->service->getMessages($conversation->id, (int) $conversation->customer_id, $beforeId, $limit);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $this->httpStatusForServiceException($e));
        }
    }

    public function sendMessage(SendWidgetMessageRequest $request, string $id): JsonResponse
    {
        $conversation = $this->authorizeConversation($request, $id);

        if (! $conversation) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'A valid conversation token is required.',
            ], 401);
        }

        try {
            $payload = $request->validated();
            $files = $request->file('attachments');
            if ($files === null && $request->hasFile('attachments')) {
                $files = $request->allFiles()['attachments'] ?? [];
            }
            $payload['attachments'] = is_array($files) ? $files : ($files ? [$files] : []);

            if (! empty($payload['attachments']) && ! $this->fileUploadEnabled($conversation)) {
                return response()->json([
                    'error' => 'File upload is disabled for this widget.',
                ], 403);
            }

            $data = $this->service->sendMessage($conversation->id, (int) $conversation->customer_id, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $data,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $this->httpStatusForServiceException($e));
        }
    }

    public function close(CloseWidgetConversationRequest $request, string $id): JsonResponse
    {
        $conversation = $this->authorizeConversation($request, $id);

        if (! $conversation) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $this->service->closeConversation($conversation->id, (int) $conversation->customer_id);

            $this->saveCsatRating($request, $conversation->id, (int) $conversation->customer_id);

            return response()->json([
                'success' => true,
                'message' => 'Conversation closed successfully',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $this->httpStatusForServiceException($e));
        }
    }

    private function saveCsatRating(CloseWidgetConversationRequest $request, int $conversationId, int $customerId): void
    {
        $rating = $request->integer('rating') ?: null;
        $feedback = $request->input('feedback');

        if ($rating === null && $feedback === null) {
            return;
        }

        CsatRating::create([
            'conversation_id' => $conversationId,
            'customer_id' => $customerId,
            'rating' => $rating,
            'comment' => $feedback,
            'answered_at' => now(),
        ]);
    }

    public function emailTranscript(EmailTranscriptRequest $request, int $id): JsonResponse
    {
        $authorized = $this->authorizeConversation($request, (string) $id);

        if (! $authorized) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $conversation = Conversation::with([
            'customer',
            'inbox.channel',
            'items' => fn ($q) => $q->with(['user:id,firstname,lastname', 'author:id,name,email'])
                ->where('is_internal', false)
                ->orderBy('created_at'),
        ])->find($id);

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Verify that email transcripts are enabled for this inbox's Web channel.
        $web = $this->resolveWebForConversation($conversation);

        if (! $web?->enable_email_transcripts) {
            return response()->json(['error' => 'Email transcripts are not enabled'], 403);
        }

        // Ensure the destination email matches the conversation owner.
        $customer = $conversation->customer;
        $destinationEmail = $request->validated('email');

        if (! $customer || strcasecmp((string) $customer->email, $destinationEmail) !== 0) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        Mail::queue(new ConversationTranscriptMail($conversation, $destinationEmail));

        return response()->json([
            'success' => true,
            'message' => 'Transcript enviado a tu correo',
        ]);
    }

    public function typing(Request $request, string $id): JsonResponse
    {
        $conversation = $this->authorizeConversation($request, $id);

        if (! $conversation) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        broadcast(new WidgetTyping($conversation->id));

        return response()->json(['success' => true]);
    }

    public function markAsRead(MarkAsReadRequest $request, string $id): JsonResponse
    {
        $conversation = $this->authorizeConversation($request, $id);

        if (! $conversation) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($upTo = $request->integer('up_to_message_id') ?: null) {
            $exists = ConversationItem::where('id', $upTo)
                ->where('conversation_id', $conversation->id)
                ->exists();

            if (! $exists) {
                return response()->json(['error' => 'Message not found in this conversation'], 404);
            }
        }

        ConversationRead::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => 0],
            ['read_at' => now()],
        );

        return response()->json(['success' => true]);
    }

    /**
     * Map service-layer RuntimeException messages to appropriate HTTP status codes.
     * 'Conversation not found' → 404, 'Unauthorized access' → 403, others → 500.
     */
    private function httpStatusForServiceException(\RuntimeException $e): int
    {
        return match (true) {
            str_contains($e->getMessage(), 'not found') => 404,
            str_contains($e->getMessage(), 'Unauthorized') => 403,
            str_contains($e->getMessage(), 'Invalid') => 422,
            default => 500,
        };
    }

    /**
     * Authorize a widget visitor's access to a conversation using the
     * per-conversation secret (`widget_pubsub_token`, stored in metadata and
     * handed to the visitor only at conversation creation).
     *
     * The customer_id is derived from the verified conversation — it is never
     * trusted from client input. This closes the IDOR where a visitor could
     * read/impersonate/close another visitor's chat by supplying a guessed,
     * sequential customer_id together with a sequential conversation id.
     *
     * The token is read from the `X-Conversation-Token` header (preferred) or
     * a `conversation_token` field, and compared in constant time.
     */
    private function authorizeConversation(Request $request, string $conversationId): ?Conversation
    {
        $conversation = Conversation::find($conversationId);
        if (! $conversation) {
            return null;
        }

        return $this->conversationTokenValid($conversation, $request) ? $conversation : null;
    }

    /**
     * Whether visitor file uploads are enabled for the conversation's Web channel.
     * Mirrors the client-side enable_file_upload flag so a tampered widget cannot
     * upload attachments when the admin has disabled them for that inbox.
     */
    private function fileUploadEnabled(Conversation $conversation): bool
    {
        $web = $this->resolveWebForConversation($conversation);

        // Default to allowed when no channel row is found (legacy/edge), but
        // deny explicitly when the admin has turned the flag off.
        return ! $web || (bool) $web->enable_file_upload;
    }

    /**
     * Resolve the Web channel that owns the conversation's inbox.
     *
     * Uses the conversation's own `inbox.channel` relationship — authoritative
     * for that conversation — instead of an extra `whereHas('inbox')` lookup.
     * Eager-load `inbox.channel` on the conversation to avoid extra queries.
     */
    private function resolveWebForConversation(Conversation $conversation): ?Web
    {
        $channel = $conversation->inbox?->channel;

        return $channel instanceof Web ? $channel : null;
    }
}
