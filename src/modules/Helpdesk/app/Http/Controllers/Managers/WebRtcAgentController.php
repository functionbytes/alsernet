<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\Managers\WebRtcAgentIceRequest;
use Modules\Helpdesk\Http\Requests\Managers\WebRtcAnswerRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskLivechat\Events\WebRtcSignal;
use Modules\HelpdeskLivechat\Models\LivestreamEvent;

class WebRtcAgentController extends Controller
{
    public function answer(WebRtcAnswerRequest $request, Conversation $conversation): JsonResponse
    {
        WebRtcSignal::dispatch(
            $conversation->id,
            'answer',
            ['sdp' => $request->validated('sdp'), 'type' => 'answer'],
            'to-widget',
        );

        return response()->json(['success' => true], 201);
    }

    public function ice(WebRtcAgentIceRequest $request, Conversation $conversation): JsonResponse
    {
        WebRtcSignal::dispatch(
            $conversation->id,
            'ice',
            ['candidate' => $request->validated('candidate')],
            'to-widget',
        );

        return response()->json(['success' => true], 201);
    }

    public function end(Conversation $conversation): JsonResponse
    {
        if (! $this->user()?->can('helpdesk.conversations.view')) {
            return response()->json(['success' => false], 403);
        }

        WebRtcSignal::dispatch($conversation->id, 'end', [], 'to-widget');

        return response()->json(['success' => true]);
    }

    public function request(Conversation $conversation): JsonResponse
    {
        if (! $this->user()?->can('helpdesk.conversations.view')) {
            return response()->json(['success' => false], 403);
        }

        $agent = $this->user();

        WebRtcSignal::dispatch(
            $conversation->id,
            'request',
            ['agent_name' => $agent?->name ?? 'Agente'],
            'to-widget',
        );

        return response()->json(['success' => true]);
    }

    /**
     * Return the most recent livestream batches for an agent that joins after
     * the visitor has already started recording. The agent player feeds these
     * to rrweb before subscribing to the live broadcast — without this the
     * player renders blank because it never receives the initial snapshot.
     */
    public function livestreamHistory(Conversation $conversation): JsonResponse
    {
        if (! $this->user()?->can('helpdesk.conversations.view')) {
            return response()->json(['events' => []], 403);
        }

        $rows = LivestreamEvent::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->limit(200)
            ->get(['events']);

        $events = [];
        foreach ($rows as $row) {
            foreach ((array) $row->events as $e) {
                $events[] = $e;
            }
        }

        return response()->json([
            'events' => $events,
            'count' => count($events),
        ]);
    }

    private function user()
    {
        return request()->user();
    }
}
