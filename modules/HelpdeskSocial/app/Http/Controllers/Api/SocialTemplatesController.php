<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialTemplateRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialTemplateRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialTemplateResource;
use Modules\HelpdeskSocial\Models\SocialTemplate;
use Modules\HelpdeskSocial\Services\AuditLogService;

class SocialTemplatesController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.templates.manage'), 403);
        $templates = SocialTemplate::query()
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => SocialTemplateResource::collection($templates),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    public function store(StoreSocialTemplateRequest $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.templates.manage'), 403);
        $validated = $request->validated();
        $validated['created_by_user_id'] = auth()->id();
        $validated['is_active'] = true;

        $template = SocialTemplate::create($validated);
        $this->auditLog->log('create', $template, null, $template->toArray());

        return response()->json([
            'data' => new SocialTemplateResource($template),
        ], 201);
    }

    public function show(SocialTemplate $template): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.templates.manage'), 403);

        return response()->json([
            'data' => new SocialTemplateResource($template),
        ]);
    }

    public function update(UpdateSocialTemplateRequest $request, SocialTemplate $template): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.templates.manage'), 403);
        $oldValues = $template->toArray();
        $template->update($request->validated());
        $this->auditLog->log('update', $template, $oldValues, $template->toArray());

        return response()->json([
            'data' => new SocialTemplateResource($template),
        ]);
    }

    public function destroy(SocialTemplate $template): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.templates.manage'), 403);
        $oldValues = $template->toArray();
        $template->delete();
        $this->auditLog->log('delete', $template, $oldValues);

        return response()->json(['message' => 'Plantilla eliminada correctamente']);
    }

    public function preview(): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.templates.manage'), 403);
        $template = request()->validate([
            'template_id' => 'required|exists:helpdesk_social_templates,id',
            'variables' => 'nullable|array',
        ]);

        $tpl = SocialTemplate::find($template['template_id']);
        $data = $template['variables'] ?? [];

        return response()->json([
            'rendered' => $tpl->render($data),
        ]);
    }
}
