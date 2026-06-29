<?php

namespace Modules\Remarketing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Remarketing\Http\Requests\Api\StoreTemplateRequest;
use Modules\Remarketing\Http\Requests\Api\UpdateTemplateRequest;
use Modules\Remarketing\Http\Resources\TemplateResource;
use Modules\Remarketing\Models\Template;

class TemplateApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Template::class);

        $templates = Template::query()
            ->latest()
            ->paginate($request->input('per_page', 15));

        return TemplateResource::collection($templates);
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = Template::create($request->validated());

        return (new TemplateResource($template))->response()->setStatusCode(201);
    }

    public function show(Template $template): TemplateResource
    {
        $this->authorize('view', $template);

        return new TemplateResource($template);
    }

    public function update(UpdateTemplateRequest $request, Template $template): TemplateResource
    {
        $this->authorize('update', $template);

        $template->update($request->validated());

        return new TemplateResource($template->fresh());
    }

    public function destroy(Template $template): JsonResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return response()->json(null, 204);
    }

    public function preview(Template $template): JsonResponse
    {
        $this->authorize('view', $template);

        return response()->json([
            'subject' => $template->subject,
            'preheader' => $template->preheader,
            'html' => $template->html_content,
        ]);
    }
}
