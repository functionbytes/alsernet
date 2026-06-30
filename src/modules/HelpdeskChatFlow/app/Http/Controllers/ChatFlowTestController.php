<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskChatFlow\Http\Requests\SimulatorSendRequest;
use Modules\HelpdeskChatFlow\Http\Requests\SimulatorUploadRequest;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowTestSimulator;

class ChatFlowTestController extends Controller
{
    public function __construct(private readonly ChatFlowTestSimulator $simulator) {}

    public function start(ChatFlow $chatFlow, Request $request): JsonResponse
    {
        $this->authorize('update', $chatFlow);

        $rawNodes = $request->input('nodes');
        $nodes = $rawNodes ? json_decode($rawNodes, true) : null;

        if (! is_array($nodes)) {
            $nodes = $chatFlow->nodes ?? [];
        }

        $result = $this->simulator->start($nodes, $request->user()?->id);

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function send(SimulatorSendRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->simulator->reply($validated['session_key'], $validated['message'], $request->user()?->id);

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function upload(ChatFlow $chatFlow, SimulatorUploadRequest $request): JsonResponse
    {
        $this->authorize('update', $chatFlow);

        $validated = $request->validated();

        // Defense in depth: the request already whitelists these to [A-Za-z0-9_-],
        // but strip any path component before they touch the filesystem.
        $sessionKey = basename($validated['session_key']);
        $docKey = basename($validated['doc_key']);

        $file = $request->file('file');
        $fileName = $docKey.'_'.time().'.'.$file->getClientOriginalExtension();
        $file->storeAs('chatflow-test/'.$sessionKey, $fileName);

        $result = $this->simulator->replyWithFile(
            $sessionKey,
            $docKey,
            $file->getClientOriginalName(),
            $request->user()?->id
        );

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }
}
