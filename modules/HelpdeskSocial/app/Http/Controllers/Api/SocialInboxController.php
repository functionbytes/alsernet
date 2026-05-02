<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Events\SocialCommentReplied;
use Modules\HelpdeskSocial\Http\Requests\BulkSocialCommentRequest;
use Modules\HelpdeskSocial\Http\Requests\ReplySocialCommentRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialCommentResource;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Services\AuditLogService;

class SocialInboxController extends Controller
{
    public function __construct(
        private readonly SocialApiClientInterface $apiClient,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);
        $query = SocialComment::with(['socialAccount', 'intent', 'assignedUser'])
            ->notSpam()
            ->when($request->get('platform'), fn ($q, $platform) => $q->where('platform', $platform))
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->get('intent'), fn ($q, $intent) => $q->where('intent', $intent))
            ->when($request->get('account_id'), fn ($q, $id) => $q->where('social_account_id', $id))
            ->when($request->get('assigned_to'), fn ($q, $id) => $q->where('assigned_to_user_id', $id))
            ->when($request->get('search'), function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('body', 'like', "%{$search}%")
                        ->orWhere('author_name', 'like', "%{$search}%");
                });
            })
            ->when($request->get('date_from'), fn ($q, $date) => $q->whereDate('posted_at', '>=', $date))
            ->when($request->get('date_to'), fn ($q, $date) => $q->whereDate('posted_at', '<=', $date));

        $sortField = $request->get('sort_by', 'posted_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortFields = ['posted_at', 'created_at', 'replied_at', 'status', 'urgency', 'intent'];
        if (! in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'posted_at';
        }

        $query->orderBy($sortField, in_array(strtolower($sortOrder), ['asc', 'desc'], true) ? strtolower($sortOrder) : 'desc');

        $comments = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'data' => SocialCommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    public function show(SocialComment $comment): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        return response()->json([
            'data' => new SocialCommentResource($comment->load(['socialAccount', 'intent', 'conversation', 'assignedUser'])),
        ]);
    }

    public function reply(ReplySocialCommentRequest $request, SocialComment $comment): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);
        $validated = $request->validated();

        if ($comment->status === 'replied') {
            return response()->json(['message' => 'Este comentario ya tiene una respuesta'], 422);
        }

        $account = $comment->socialAccount;
        $replyId = $this->apiClient->replyToComment(
            $comment->external_comment_id,
            $validated['body'],
            $account->page_access_token,
            $comment->platform
        );

        if (! $replyId) {
            return response()->json(['message' => 'Error al enviar la respuesta a la red social'], 500);
        }

        $comment->markAsReplied($validated['body'], auth()->id(), $replyId, 'manual');
        $this->auditLog->log('reply', $comment, null, ['reply_body' => $validated['body']]);
        SocialCommentReplied::dispatch($comment->fresh());

        return response()->json([
            'data' => new SocialCommentResource($comment->fresh()),
        ]);
    }

    public function markAsSpam(SocialComment $comment): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);
        $oldValues = ['is_spam' => $comment->is_spam, 'status' => $comment->status];
        $comment->markAsSpam();
        $this->auditLog->log('update', $comment, $oldValues, ['is_spam' => true, 'status' => 'spam']);

        return response()->json([
            'data' => new SocialCommentResource($comment->fresh()),
        ]);
    }

    public function markAsEscalated(SocialComment $comment): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);
        $oldValues = ['status' => $comment->status, 'assigned_to_user_id' => $comment->assigned_to_user_id];
        $comment->markAsEscalated();
        $this->auditLog->log('escalate', $comment, $oldValues, ['status' => 'escalated']);

        return response()->json([
            'data' => new SocialCommentResource($comment->fresh()),
        ]);
    }

    public function assign(Request $request, SocialComment $comment): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $oldValues = ['assigned_to_user_id' => $comment->assigned_to_user_id];
        $comment->assignTo($validated['user_id']);
        $this->auditLog->log('assign', $comment, $oldValues, ['assigned_to_user_id' => $validated['user_id']]);

        return response()->json([
            'data' => new SocialCommentResource($comment->fresh()->load('assignedUser')),
        ]);
    }

    public function bulk(BulkSocialCommentRequest $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);
        $validated = $request->validated();
        $ids = $validated['ids'];
        $action = $validated['action'];
        $params = $validated['params'] ?? [];

        $comments = SocialComment::whereIn('id', $ids)->get();
        $results = ['processed' => 0, 'failed' => 0];

        foreach ($comments as $comment) {
            try {
                match ($action) {
                    'spam' => $comment->markAsSpam(),
                    'escalate' => $comment->markAsEscalated($params['user_id'] ?? null),
                    'assign' => $comment->assignTo($params['user_id'] ?? null),
                    default => null,
                };
                $results['processed']++;
            } catch (\Throwable $e) {
                $results['failed']++;
            }
        }

        return response()->json([
            'message' => "Procesados {$results['processed']} comentarios.",
            'results' => $results,
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);
        $query = SocialComment::query();

        if ($request->get('platform')) {
            $query->where('platform', $request->get('platform'));
        }

        if ($request->get('account_id')) {
            $query->where('social_account_id', $request->get('account_id'));
        }

        return response()->json([
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'replied' => (clone $query)->where('status', 'replied')->count(),
            'auto_replied' => (clone $query)->where('reply_type', 'auto')->count(),
            'escalated' => (clone $query)->where('status', 'escalated')->count(),
            'spam' => (clone $query)->where('is_spam', true)->count(),
            'intents' => (clone $query)
                ->selectRaw('intent, count(*) as count')
                ->whereNotNull('intent')
                ->groupBy('intent')
                ->pluck('count', 'intent'),
        ]);
    }
}
