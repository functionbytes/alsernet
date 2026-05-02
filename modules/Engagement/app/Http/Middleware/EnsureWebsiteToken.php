<?php

namespace Modules\Engagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnsureWebsiteToken
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $token = $request->header('X-Website-Token')
            ?? $request->input('website_token');

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Token del sitio web requerido.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $channel = Web::query()->where('website_token', $token)->first();

        if (! $channel) {
            return response()->json([
                'success' => false,
                'message' => 'Token del sitio web no válido.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $inbox = $channel->inbox;

        if (! $inbox || ! $inbox->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'El canal web no está activo.',
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('livechat_channel', $channel);
        $request->attributes->set('livechat_inbox', $inbox);

        return $next($request);
    }
}
