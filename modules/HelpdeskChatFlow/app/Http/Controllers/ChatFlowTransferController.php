<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Modules\HelpdeskChatFlow\Http\Requests\ImportChatFlowRequest;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowValidator;

/**
 * Exportación/importación de flows como JSON (formato chatflow/v1).
 */
class ChatFlowTransferController extends Controller
{
    public function __construct(
        private readonly ChatFlowValidator $validator,
    ) {}

    public function export(ChatFlow $chatFlow): JsonResponse
    {
        $this->authorize('view', $chatFlow);

        $payload = [
            'format' => 'chatflow/v1',
            'name' => $chatFlow->name,
            'description' => $chatFlow->description,
            'trigger_type' => $chatFlow->trigger_type,
            'trigger_conditions' => $chatFlow->trigger_conditions,
            'nodes' => $chatFlow->nodes,
            'exported_at' => now()->toIso8601String(),
        ];

        $filename = Str::slug($chatFlow->name ?: 'chatflow').'-flow.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function import(ImportChatFlowRequest $request): RedirectResponse
    {
        // Structural validation (JSON shape, node/edge caps, node types, start
        // node, trigger_type and trigger_conditions shape) lives in
        // ImportChatFlowRequest. Here only the runtime graph validation runs —
        // the same validator used on publish, so we never import a flow that
        // would break at runtime (broken go_to_step, unreachable nodes, etc.).
        $data = $request->flowData();

        $candidate = new ChatFlow(['nodes' => $data['nodes']]);
        $validation = $this->validator->validate($candidate);

        if (! empty($validation['errors'])) {
            return back()->with('error', 'El flow importado no es válido: '.implode(' ', $validation['errors']));
        }

        $flow = ChatFlow::create([
            'uid' => Str::uuid(),
            'name' => ($data['name'] ?? 'Flow importado').' (importado)',
            'description' => $data['description'] ?? null,
            'trigger_type' => in_array($data['trigger_type'] ?? '', ChatFlow::TRIGGER_TYPES, true)
                ? $data['trigger_type']
                : 'conversation_start',
            'trigger_conditions' => is_array($data['trigger_conditions'] ?? null) ? $data['trigger_conditions'] : null,
            'nodes' => $data['nodes'],
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('chatflow.edit', $flow)->with('success', 'Flow importado como borrador. Revísalo y publícalo.');
    }
}
