<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialSlaPolicyRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialSlaPolicyRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialSlaPolicyResource;
use Modules\HelpdeskSocial\Models\SocialSlaPolicy;
use Modules\HelpdeskSocial\Services\AuditLogService;

class SocialSlaPoliciesController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $policies = SocialSlaPolicy::orderBy('name')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => SocialSlaPolicyResource::collection($policies),
            'meta' => [
                'currentPage' => $policies->currentPage(),
                'lastPage' => $policies->lastPage(),
                'perPage' => $policies->perPage(),
                'total' => $policies->total(),
            ],
        ]);
    }

    public function store(StoreSocialSlaPolicyRequest $request): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $policy = SocialSlaPolicy::create($request->validated());
        $this->auditLog->log('create', $policy, null, $policy->toArray());

        return ApiResponse::created(new SocialSlaPolicyResource($policy), 'Política SLA creada correctamente.');
    }

    public function show(SocialSlaPolicy $policy): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        return ApiResponse::success(new SocialSlaPolicyResource($policy));
    }

    public function update(UpdateSocialSlaPolicyRequest $request, SocialSlaPolicy $policy): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $oldValues = $policy->toArray();
        $policy->update($request->validated());
        $this->auditLog->log('update', $policy, $oldValues, $policy->toArray());

        return ApiResponse::success(new SocialSlaPolicyResource($policy), 'Política SLA actualizada correctamente.');
    }

    public function destroy(SocialSlaPolicy $policy): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $oldValues = $policy->toArray();
        $policy->delete();
        $this->auditLog->log('delete', $policy, $oldValues);

        return ApiResponse::noContent();
    }
}
