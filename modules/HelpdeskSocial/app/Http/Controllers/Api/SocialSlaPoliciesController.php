<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
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
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);
        $policies = SocialSlaPolicy::orderBy('name')->paginate(20);

        return response()->json([
            'data' => SocialSlaPolicyResource::collection($policies),
            'meta' => [
                'current_page' => $policies->currentPage(),
                'last_page' => $policies->lastPage(),
                'per_page' => $policies->perPage(),
                'total' => $policies->total(),
            ],
        ]);
    }

    public function store(StoreSocialSlaPolicyRequest $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);
        $policy = SocialSlaPolicy::create($request->validated());
        $this->auditLog->log('create', $policy, null, $policy->toArray());

        return response()->json([
            'data' => new SocialSlaPolicyResource($policy),
        ], 201);
    }

    public function show(SocialSlaPolicy $policy): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);

        return response()->json([
            'data' => new SocialSlaPolicyResource($policy),
        ]);
    }

    public function update(UpdateSocialSlaPolicyRequest $request, SocialSlaPolicy $policy): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);
        $oldValues = $policy->toArray();
        $policy->update($request->validated());
        $this->auditLog->log('update', $policy, $oldValues, $policy->toArray());

        return response()->json([
            'data' => new SocialSlaPolicyResource($policy),
        ]);
    }

    public function destroy(SocialSlaPolicy $policy): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);
        $oldValues = $policy->toArray();
        $policy->delete();
        $this->auditLog->log('delete', $policy, $oldValues);

        return response()->json(['message' => 'Política SLA eliminada correctamente']);
    }
}
