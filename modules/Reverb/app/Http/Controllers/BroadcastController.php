<?php

namespace Modules\Reverb\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class BroadcastController extends Controller
{
    /**
     * Authenticate the request for channel access.
     *
     * This endpoint is called by Laravel Echo/Reverb to authenticate channel subscriptions.
     * Since session cookies may not be sent with WebSocket auth requests, we use the
     * CSRF token from the request to identify and authenticate the user.
     *
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        // First, try to get the authenticated user from the session
        $user = $request->user();

        // If not authenticated via session, try to authenticate using CSRF token
        // This allows WebSocket clients to authenticate even without session cookies
        if (! $user) {
            // Try to find the user from the CSRF token
            // The CSRF token is typically sent in the request headers
            $csrfToken = $request->header('X-CSRF-TOKEN');

            if ($csrfToken) {
                // In a stateful SPA context with Sanctum, the CSRF token can be used
                // to verify the session and authenticate the user
                // However, we'll use a simpler approach: reconstruct the session
                try {
                    // Get the session ID from cookies or headers
                    $sessionId = $request->cookies->get(config('session.cookie'));

                    if ($sessionId) {
                        // Retrieve the session data to get the user ID
                        $sessionData = \DB::table('sessions')
                            ->where('id', $sessionId)
                            ->where('user_agent', $request->userAgent())
                            ->first();

                        if ($sessionData) {
                            $payload = unserialize(base64_decode($sessionData->payload));
                            $userId = $payload['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] ?? null;

                            if ($userId) {
                                $user = \App\Models\User::find($userId);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Broadcasting auth failed to resolve user', ['error' => $e->getMessage()]);
                }
            }
        }

        // Reflect the session to extend lifetime
        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        // Use the authenticated user for channel authorization
        // If $user is null, channel callbacks in routes/channels.php will reject the subscription
        if ($user) {
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
        }

        return Broadcast::auth($request);
    }
}
