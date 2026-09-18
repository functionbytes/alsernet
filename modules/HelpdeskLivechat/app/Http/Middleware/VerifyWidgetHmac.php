<?php

namespace Modules\HelpdeskLivechat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates X-Identifier-Hash header against HMAC-SHA256(identifier, hmac_token).
 * Only enforced when Web::enforce_identity_verification is true.
 *
 * Resolved Web records are cached for 5 minutes and stored in request attributes
 * as 'widget_web' so downstream controllers can reuse them without extra queries.
 */
class VerifyWidgetHmac
{
    public function handle(Request $request, Closure $next): Response
    {
        $websiteToken = $request->input('website_token')
            ?? $request->route('websiteToken')
            ?? $request->header('X-Website-Token');

        if (! $websiteToken) {
            return $next($request);
        }

        $web = Cache::remember(
            'helpdesklivechat:web:token:'.$websiteToken,
            now()->addMinutes(5),
            fn () => Web::query()->where('website_token', $websiteToken)->first()
        );

        // Store resolved web in request attributes for downstream reuse.
        if ($web) {
            $request->attributes->set('widget_web', $web);
        }

        if (! $web || ! ($web->enforce_identity_verification ?? false)) {
            return $next($request);
        }

        $identifier = $request->input('identifier')
            ?? $request->input('customer_email')
            ?? $request->input('customer_id');

        $hash = $request->header('X-Identifier-Hash')
            ?? $request->input('identifier_hash');

        if (! $identifier) {
            return $next($request);
        }

        if (! $hash) {
            return response()->json(['error' => 'Missing identifier hash'], 403);
        }

        $expected = hash_hmac('sha256', (string) $identifier, $web->hmac_token);

        if (! hash_equals($expected, (string) $hash)) {
            return response()->json(['error' => 'Invalid identifier hash'], 403);
        }

        return $next($request);
    }
}
