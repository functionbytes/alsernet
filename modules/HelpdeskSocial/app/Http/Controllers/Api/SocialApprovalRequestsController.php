<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpdeskSocial\Http\Requests\RespondSocialApprovalRequestRequest;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialApprovalRequestRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialApprovalRequestResource;
use Modules\HelpdeskSocial\Models\SocialApprovalRequest;
use Modules\HelpdeskSocial\Services\AuditLogService;

class SocialApprovalRequestsController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $query = SocialApprovalRequest::with(['requester', 'approver', 'comment']);

        if ($request->boolean('pending_for_me')) {
            $query->pending()->where('approver_user_id', auth()->id());
        }

        $approvalRequests = $query->orderByDesc('created_at')->paginate(25);

        return response()->json([
            'data' => SocialApprovalRequestResource::collection($approvalRequests),
            'meta' => [
                'current_page' => $approvalRequests->currentPage(),
                'last_page' => $approvalRequests->lastPage(),
                'per_page' => $approvalRequests->perPage(),
                'total' => $approvalRequests->total(),
            ],
        ]);
    }

    public function store(StoreSocialApprovalRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['requested_by_user_id'] = auth()->id();
        $validated['status'] = 'pending';

        $approvalRequest = SocialApprovalRequest::create($validated);

        return response()->json([
            'data' => new SocialApprovalRequestResource($approvalRequest->load(['requester', 'approver', 'comment'])),
        ], 201);
    }

    public function show(SocialApprovalRequest $approvalRequest): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        return response()->json([
            'data' => new SocialApprovalRequestResource($approvalRequest->load(['requester', 'approver', 'comment'])),
        ]);
    }

    public function approve(RespondSocialApprovalRequestRequest $request, SocialApprovalRequest $approvalRequest): JsonResponse
    {
        abort_if($approvalRequest->status !== 'pending', 422, 'La solicitud ya fue respondida.');

        $approvalRequest->approve($request->validated()['approver_note'] ?? null);
        $this->auditLog->log('approve', $approvalRequest, ['status' => 'pending'], ['status' => 'approved']);

        return response()->json([
            'data' => new SocialApprovalRequestResource($approvalRequest->fresh()->load(['requester', 'approver', 'comment'])),
        ]);
    }

    public function reject(RespondSocialApprovalRequestRequest $request, SocialApprovalRequest $approvalRequest): JsonResponse
    {
        abort_if($approvalRequest->status !== 'pending', 422, 'La solicitud ya fue respondida.');

        $approvalRequest->reject($request->validated()['approver_note'] ?? null);
        $this->auditLog->log('reject', $approvalRequest, ['status' => 'pending'], ['status' => 'rejected']);

        return response()->json([
            'data' => new SocialApprovalRequestResource($approvalRequest->fresh()->load(['requester', 'approver', 'comment'])),
        ]);
    }
}
