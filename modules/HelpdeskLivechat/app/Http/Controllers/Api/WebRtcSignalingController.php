<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Concerns\VerifiesConversationToken;
use Modules\HelpdeskLivechat\Events\WebRtcSignal;
use Modules\HelpdeskLivechat\Http\Requests\Widget\WebRtcIceRequest;
use Modules\HelpdeskLivechat\Http\Requests\Widget\WebRtcOfferRequest;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class WebRtcSignalingController extends Controller
{
    use VerifiesConversationToken;

    public function offer(WebRtcOfferRequest $request, Conversation $conversation): JsonResponse
    {
        if (! $this->conversationBelongsToToken($conversation, $request)) {
            return response()->json(['success' => false], 403);
        }

        WebRtcSignal::dispatch(
            $conversation->id,
            'offer',
            ['sdp' => $request->validated('sdp'), 'type' => 'offer'],
            'to-agent',
        );

        return response()->json(['success' => true], 201);
    }

    public function ice(WebRtcIceRequest $request, Conversation $conversation): JsonResponse
    {
        if (! $this->conversationBelongsToToken($conversation, $request)) {
            return response()->json(['success' => false], 403);
        }

        WebRtcSignal::dispatch(
            $conversation->id,
            'ice',
            ['candidate' => $request->validated('candidate')],
            'to-agent',
        );

        return response()->json(['success' => true], 201);
    }

    public function end(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->conversationBelongsToToken($conversation, $request)) {
            return response()->json(['success' => false], 403);
        }

        WebRtcSignal::dispatch($conversation->id, 'end', [], 'to-agent');

        return response()->json(['success' => true]);
    }

    /**
     * Verify that the conversation belongs to the inbox identified by the website token.
     * Uses the widget_web request attribute (set by VerifyWidgetHmac middleware) when available,
     * falling back to a cached Web lookup.
     */
    private function conversationBelongsToToken(Conversation $conversation, Request $request): bool
    {
        // Per-conversation secret — only the owning visitor may signal, not any
        // visitor whose website token matches this inbox.
        if (! $this->conversationTokenValid($conversation, $request)) {
            return false;
        }

        $token = (string) $request->header('X-Website-Token');

        if ($token === '') {
            return false;
        }

        $web = $request->attributes->get('widget_web')
            ?? Cache::remember(
                'helpdesklivechat:web:token:'.$token,
                now()->addMinutes(5),
                fn () => Web::query()->where('website_token', $token)->first()
            );

        if (! $web) {
            return false;
        }

        // Re-verify the screen-share feature flag server-side.
        if (! $web->enable_screen_share) {
            return false;
        }

        return Inbox::query()
            ->where('id', $conversation->inbox_id)
            ->where('channel_type', 'web')
            ->where('channel_id', $web->id)
            ->exists();
    }
}
