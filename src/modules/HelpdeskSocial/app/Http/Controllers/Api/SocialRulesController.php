<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialRuleRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialRuleRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialRuleResource;
use Modules\HelpdeskSocial\Models\SocialRule;
use Modules\HelpdeskSocial\Services\AuditLogService;
use Modules\HelpdeskSocial\Services\Classifiers\RulesIntentClassifier;

class SocialRulesController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $rules = SocialRule::query()
            ->ordered()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => SocialRuleResource::collection($rules),
            'meta' => [
                'currentPage' => $rules->currentPage(),
                'lastPage' => $rules->lastPage(),
                'perPage' => $rules->perPage(),
                'total' => $rules->total(),
            ],
        ]);
    }

    public function store(StoreSocialRuleRequest $request): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $validated = $request->validated();
        $validated['created_by_user_id'] = auth()->id();
        $validated['is_active'] = true;

        $rule = SocialRule::create($validated);
        $this->auditLog->log('create', $rule, null, $rule->toArray());

        return ApiResponse::created(new SocialRuleResource($rule), 'Regla creada correctamente.');
    }

    public function show(SocialRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        return ApiResponse::success(new SocialRuleResource($rule));
    }

    public function update(UpdateSocialRuleRequest $request, SocialRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $oldValues = $rule->toArray();
        $rule->update($request->validated());
        $this->auditLog->log('update', $rule, $oldValues, $rule->toArray());

        return ApiResponse::success(new SocialRuleResource($rule), 'Regla actualizada correctamente.');
    }

    public function destroy(SocialRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $oldValues = $rule->toArray();
        $rule->delete();
        $this->auditLog->log('delete', $rule, $oldValues);

        return ApiResponse::noContent();
    }

    public function toggleActive(SocialRule $rule): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $oldValues = ['is_active' => $rule->is_active];
        $rule->update(['is_active' => ! $rule->is_active]);
        $this->auditLog->log('update', $rule, $oldValues, ['is_active' => $rule->is_active]);

        return ApiResponse::success(new SocialRuleResource($rule));
    }

    public function simulate(): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);
        $validated = request()->validate([
            'body' => 'required|string',
            'platform' => 'required|string|in:facebook,instagram',
            'intent' => 'nullable|string',
        ]);

        $classifier = app(RulesIntentClassifier::class);
        $classification = $classifier->classifyText($validated['body']);

        $rules = SocialRule::active()
            ->forPlatform($validated['platform'])
            ->validNow()
            ->ordered()
            ->get();

        return ApiResponse::success([
            'classification' => $classification,
            'matched_rules' => SocialRuleResource::collection($rules),
        ]);
    }
}
