<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Http\Responses\ApiResponse;
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
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $rules = SocialAssignmentRule::with('assignee')
            ->ordered()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => SocialAssignmentRuleResource::collection($rules),
            'meta' => [
                'currentPage' => $rules->currentPage(),
                'lastPage' => $rules->lastPage(),
                'perPage' => $rules->perPage(),
                'total' => $rules->total(),
            ],
        ]);
    }

    public function store(StoreSocialAssignmentRuleRequest $request): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? true;

        $rule = SocialAssignmentRule::create($validated);
        $this->auditLog->log('create', $rule, null, $rule->toArray());

        return ApiResponse::created(new SocialAssignmentRuleResource($rule), 'Regla de asignación creada correctamente.');
    }

    public function show(SocialAssignmentRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        return ApiResponse::success(new SocialAssignmentRuleResource($rule->load('assignee')));
    }

    public function update(UpdateSocialAssignmentRuleRequest $request, SocialAssignmentRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $oldValues = $rule->toArray();
        $rule->update($request->validated());
        $this->auditLog->log('update', $rule, $oldValues, $rule->toArray());

        return ApiResponse::success(new SocialAssignmentRuleResource($rule->load('assignee')), 'Regla de asignación actualizada correctamente.');
    }

    public function destroy(SocialAssignmentRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $oldValues = $rule->toArray();
        $rule->delete();
        $this->auditLog->log('delete', $rule, $oldValues);

        return ApiResponse::noContent();
    }

    public function toggleActive(SocialAssignmentRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $oldValues = ['is_active' => $rule->is_active];
        $rule->update(['is_active' => ! $rule->is_active]);
        $this->auditLog->log('update', $rule, $oldValues, ['is_active' => $rule->is_active]);

        return ApiResponse::success(new SocialAssignmentRuleResource($rule->load('assignee')));
    }
}
