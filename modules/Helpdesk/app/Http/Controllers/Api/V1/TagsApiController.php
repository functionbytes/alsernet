<?php

namespace Modules\Helpdesk\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Resources\ConversationTagResource;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\Helpdesk\Models\ConversationTag;

/**
 * @group Tags
 *
 * Manage conversation tags.
 */
class TagsApiController extends Controller
{
    /**
     * List tags
     *
     * Returns all active conversation tags.
     *
     * @queryParam per_page integer Results per page (default 50). Example: 50
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.tags.view');

        $tags = ConversationTag::query()
            ->where('is_active', true)
            ->withCount('conversations')
            ->orderBy('name')
            ->paginate($request->input('per_page', 50));

        return ApiResponse::success(ConversationTagResource::collection($tags));
    }

    /**
     * Create tag
     *
     * @bodyParam name string required Tag name. Example: billing
     * @bodyParam color string Hex color code. Example: #90bb13
     * @bodyParam description string Optional description. Example: Billing-related issues
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.tags.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:helpdesk.helpdesk_conversation_tags,name'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'El nombre de la etiqueta es obligatorio.',
            'name.unique' => 'Ya existe una etiqueta con ese nombre.',
            'color.regex' => 'El color debe ser un código hexadecimal válido.',
        ]);

        $tag = ConversationTag::create($validated);

        return ApiResponse::created(new ConversationTagResource($tag), 'Etiqueta creada correctamente.');
    }
}
