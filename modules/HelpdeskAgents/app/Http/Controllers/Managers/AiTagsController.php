<?php

namespace Modules\HelpdeskAgents\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskAgents\Http\Requests\StoreAiAgentTagRequest;
use Modules\HelpdeskAgents\Http\Requests\UpdateAiAgentTagRequest;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentTag;

class AiTagsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AiAgent::class);

        $tags = AiAgentTag::orderBy('priority', 'desc')->paginate(50);

        return view('helpdeskagents::managers.ai-agent.partials.tags-tab', compact('tags'));
    }

    public function store(StoreAiAgentTagRequest $request): JsonResponse
    {
        $this->authorize('create', AiAgent::class);

        $validated = $request->validated();
        $validated['is_active'] = $request->has('is_active');

        AiAgentTag::create($validated);

        return response()->json(['success' => true, 'message' => 'Tag creado correctamente']);
    }

    public function update(UpdateAiAgentTagRequest $request, AiAgentTag $tag): JsonResponse
    {
        $this->authorize('update', AiAgent::class);

        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');
        $tag->update($validated);

        return response()->json(['success' => true, 'message' => 'Tag actualizado correctamente']);
    }

    public function destroy(AiAgentTag $tag): JsonResponse
    {
        $this->authorize('delete', AiAgent::class);

        $tag->delete();

        return response()->json(['success' => true, 'message' => 'Tag eliminado correctamente']);
    }

    public function toggle(Request $request, AiAgentTag $tag): JsonResponse
    {
        $this->authorize('update', AiAgent::class);

        $tag->update(['is_active' => $request->boolean('is_active')]);

        return response()->json(['success' => true]);
    }
}
