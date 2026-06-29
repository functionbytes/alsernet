<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Affiliate;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($code = $request->query('ref')) {
            $affiliate = Affiliate::query()
                ->where('code', $code)
                ->where('status', 'active')
                ->first();

            if ($affiliate) {
                cookie()->queue('affiliate_ref', $affiliate->id, 60 * 24 * 30);
            }
        }

        return $next($request);
    }
}
