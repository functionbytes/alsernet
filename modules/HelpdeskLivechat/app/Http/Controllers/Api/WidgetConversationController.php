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
use Modules\Helpdesk\Models\Customer;
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
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = $this->service->getConversation((int) $id, $customerId);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 404);
        }
    }

    public function getMessages(Request $request, string $id): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $beforeId = $request->filled('before_id') ? (int) $request->input('before_id') : null;
            $limit = (int) $request->input('limit', 100);

            $data = $this->service->getMessages((int) $id, $customerId, $beforeId, $limit);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve messages',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    public function sendMessage(SendWidgetMessageRequest $request, string $id): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Customer ID or email is required.',
            ], 401);
        }

        try {
            $payload = $request->validated();
            $files = $request->file('attachments');
            if ($files === null && $request->hasFile('attachments')) {
                $files = $request->allFiles()['attachments'] ?? [];
            }
            $payload['attachments'] = is_array($files) ? $files : ($files ? [$files] : []);

            $data = $this->service->sendMessage((int) $id, $customerId, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to send message',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    public function close(CloseWidgetConversationRequest $request, string $id): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $this->service->closeConversation((int) $id, $customerId);

            $this->saveCsatRating($request, (int) $id, $customerId);

            return response()->json([
                'success' => true,
                'message' => 'Conversation closed successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to close conversation',
                'message' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
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
        $customerId = $this->resolveCustomerId($request, (string) $id);

        if (! $customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $conversation = Conversation::with([
            'customer',
            'items' => fn ($q) => $q->with(['user:id,firstname,lastname', 'author:id,name,email'])
                ->where('is_internal', false)
                ->orderBy('created_at'),
        ])->find($id);

        if (! $conversation || (int) $conversation->customer_id !== $customerId) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Verify that email transcripts are enabled for this inbox's Web channel.
        $web = Web::query()
            ->whereHas('inbox', fn ($q) => $q->where('id', $conversation->inbox_id))
            ->first();

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
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $conversationId = (int) $id;

        if (! Conversation::query()->whereKey($conversationId)->where('customer_id', $customerId)->exists()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        broadcast(new WidgetTyping($conversationId));

        return response()->json(['success' => true]);
    }

    public function markAsRead(MarkAsReadRequest $request, string $id): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request, $id);

        if (! $customerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $conversationId = (int) $id;

        if (! Conversation::query()->whereKey($conversationId)->where('customer_id', $customerId)->exists()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($upTo = $request->integer('up_to_message_id') ?: null) {
            $exists = ConversationItem::where('id', $upTo)
                ->where('conversation_id', $conversationId)
                ->exists();

            if (! $exists) {
                return response()->json(['error' => 'Message not found in this conversation'], 404);
            }
        }

        ConversationRead::updateOrCreate(
            ['conversation_id' => $conversationId, 'user_id' => 0],
            ['read_at' => now()],
        );

        return response()->json(['success' => true]);
    }

    /**
     * Accept either an explicit customer_id or a customer_email that matches
     * the conversation's owner.
     */
    private function resolveCustomerId(Request $request, string $conversationId): ?int
    {
        $customerId = (int) $request->input('customer_id');
        if ($customerId) {
            return $customerId;
        }

        $email = $request->input('customer_email');
        if (! $email) {
            return null;
        }

        $conversation = Conversation::find($conversationId);
        if (! $conversation) {
            return null;
        }

        return Customer::where('id', $conversation->customer_id)
            ->where('email', $email)
            ->value('id');
    }
}
