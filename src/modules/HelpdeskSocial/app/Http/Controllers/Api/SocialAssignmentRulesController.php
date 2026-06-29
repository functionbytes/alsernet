<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialAssignmentRuleRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialAssignmentRuleRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialAssignmentRuleResource;
use Modules\HelpdeskSocial\Models\SocialAssignmentRule;
use Modules\HelpdeskSocial\Services\AuditLogService;

class SocialAssignmentRulesController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.rules.manage'), 403);
        $rules = SocialAssignmentRule::with('assignee')
            ->ordered()
            ->paginate(20);

        return response()->json([
            'data' => SocialAssignmentRuleResource::collection($rules),
            'meta' => [
                'current_page' => $rules->currentPage(),
                'last_page' => $rules->lastPage(),
                'per_page' => $rules->perPage(),
                'total' => $rules->total(),
            ],
        ]);
    }

    public function store(StoreSocialAssignmentRuleRequest $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.rules.manage'), 403);
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? true;

        $rule = SocialAssignmentRule::create($validated);
        $this->auditLog->log('create', $rule, null, $rule->toArray());

        return response()->json([
            'data' => new SocialAssignmentRuleResource($rule),
        ], 201);
    }

    public function show(SocialAssignmentRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.rules.manage'), 403);

        return response()->json([
            'data' => new SocialAssignmentRuleResource($rule->load('assignee')),
        ]);
    }

    public function update(UpdateSocialAssignmentRuleRequest $request, SocialAssignmentRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.rules.manage'), 403);
        $oldValues = $rule->toArray();
        $rule->update($request->validated());
        $this->auditLog->log('update', $rule, $oldValues, $rule->toArray());

        return response()->json([
            'data' => new SocialAssignmentRuleResource($rule->load('assignee')),
        ]);
    }

    public function destroy(SocialAssignmentRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.rules.manage'), 403);
        $oldValues = $rule->toArray();
        $rule->delete();
        $this->auditLog->log('delete', $rule, $oldValues);

        return response()->json(['message' => 'Regla de asignación eliminada correctamente']);
    }

    public function toggleActive(SocialAssignmentRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.rules.manage'), 403);
        $oldValues = ['is_active' => $rule->is_active];
        $rule->update(['is_active' => ! $rule->is_active]);
        $this->auditLog->log('update', $rule, $oldValues, ['is_active' => $rule->is_active]);

        return response()->json([
            'data' => new SocialAssignmentRuleResource($rule->load('assignee')),
        ]);
    }
}
