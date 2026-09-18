<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Concerns\VerifiesConversationToken;
use Modules\HelpdeskLivechat\Events\LivestreamBatchReceived;
use Modules\HelpdeskLivechat\Http\Requests\Widget\IngestLivestreamEventsRequest;
use Modules\HelpdeskLivechat\Jobs\StoreLivestreamBatchJob;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class LivestreamController extends Controller
{
    use VerifiesConversationToken;

    public function ingest(IngestLivestreamEventsRequest $request, Conversation $conversation): JsonResponse
    {
        // Per-conversation secret — a visitor may only stream their own session,
        // not any conversation that happens to share the inbox.
        if (! $this->conversationTokenValid($conversation, $request)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $token = (string) $request->header('X-Website-Token', '');

        $web = Cache::remember(
            'helpdesklivechat:web:token:'.$token,
            now()->addMinutes(5),
            fn () => Web::query()->where('website_token', $token)->first()
        );

        if (! $web) {
            return response()->json(['success' => false, 'message' => 'Token desconocido'], 403);
        }

        // Re-verify the live-view feature flag server-side: a tampered widget
        // cannot capture the DOM (rrweb) when the admin disabled it for this inbox.
        if (! $web->enable_live_view) {
            return response()->json(['success' => false, 'message' => 'Live view deshabilitado'], 403);
        }

        $belongs = Inbox::query()
            ->where('id', $conversation->inbox_id)
            ->where('channel_type', 'web')
            ->where('channel_id', $web->id)
            ->exists();

        if (! $belongs) {
            return response()->json(['success' => false, 'message' => 'Conversación no pertenece a este widget'], 403);
        }

        $events = $request->validated('events');
        $batchIndex = (int) $request->validated('batch_index');

        // Broadcast primero (latencia mínima al agente), persistencia en background.
        LivestreamBatchReceived::dispatch($conversation->id, $events, $batchIndex);
        StoreLivestreamBatchJob::dispatch($conversation->id, $events, $batchIndex);

        return response()->json(['success' => true], 201);
    }
}
