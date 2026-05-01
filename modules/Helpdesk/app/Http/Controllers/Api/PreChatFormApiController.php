<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\PreChatForm;

class PreChatFormApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $inboxId = $request->query('inbox_id') ? (int) $request->query('inbox_id') : null;

        $form = PreChatForm::findForInbox($inboxId);

        if (! $form) {
            return response()->json(['form' => null]);
        }

        return response()->json([
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'fields' => $form->fields,
            ],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => ['required', 'integer'],
            'data' => ['required', 'array'],
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        $metadata = $conversation->metadata ?? [];
        $metadata['pre_chat'] = $request->input('data', []);

        $conversation->update(['metadata' => $metadata]);

        return response()->json(['success' => true]);
    }
}
