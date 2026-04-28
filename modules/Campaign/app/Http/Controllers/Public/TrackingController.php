<?php

namespace Modules\Campaign\Http\Controllers\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Library\GeoIp;
use Modules\Campaign\Library\WebhookDispatcher;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignClickLog;
use Modules\Campaign\Models\CampaignLink;
use Modules\Campaign\Models\CampaignOpenLog;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\CampaignTrackingLog;
use Modules\Campaign\Models\CampaignUnsubscribeLog;

/**
 * Endpoints públicos de tracking. Ruteado bajo /campaign/track/...
 *
 * NO requieren auth. Devuelven respuestas idempotentes (no fallan si el
 * tracking_log no existe — se ignoran silenciosamente para evitar leak
 * de información o errores 500 en clientes de email).
 */
class TrackingController extends Controller
{
    /**
     * GET /campaign/track/open/{messageId}.png
     * Devuelve un GIF/PNG transparente 1x1 e inserta una fila en campaign_open_logs.
     */
    public function open(Request $request, string $messageId): Response
    {
        $log = CampaignTrackingLog::where('message_id', $messageId)->first();
        if ($log) {
            CampaignOpenLog::create([
                'tracking_log_id' => $log->id,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'country' => GeoIp::country($request->ip()),
            ]);

            // Webhook 'opened' (sólo en la PRIMERA apertura)
            if ($log->fresh()->openLogs()->count() === 1 && $log->campaign_id) {
                if ($campaign = Campaign::find($log->campaign_id)) {
                    WebhookDispatcher::emit($campaign, 'opened', [
                        'email' => $log->email,
                        'message_id' => $messageId,
                    ]);
                }
            }
        }

        // GIF 1x1 transparente
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * GET /campaign/track/click/{messageId}/{linkHash}
     * Inserta fila en campaign_click_logs y redirige a la URL original.
     */
    public function click(Request $request, string $messageId, string $linkHash)
    {
        $log = CampaignTrackingLog::where('message_id', $messageId)->first();
        $link = $log
            ? CampaignLink::where('campaign_id', $log->campaign_id)
                ->where('hash', $linkHash)
                ->first()
            : null;

        if ($log && $link) {
            CampaignClickLog::create([
                'tracking_log_id' => $log->id,
                'campaign_link_id' => $link->id,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'country' => GeoIp::country($request->ip()),
            ]);

            if ($log->campaign_id && $campaign = Campaign::find($log->campaign_id)) {
                WebhookDispatcher::emit($campaign, 'clicked', [
                    'email' => $log->email,
                    'message_id' => $messageId,
                    'url' => $link->url,
                ]);
            }
        }

        $url = $link?->url ?: ($request->query('url') ?: config('app.url'));

        return redirect()->away($url);
    }

    /**
     * GET /campaign/track/unsubscribe/{subscriberUid}/{messageId}
     * Marca al suscriptor como unsubscribed en TODAS sus listas (one-click).
     */
    public function unsubscribe(string $subscriberUid, string $messageId): Response
    {
        $sub = CampaignSubscriber::where('uid', $subscriberUid)->first();

        if ($sub) {
            DB::table('campaign_maillists_subscribers')
                ->where('subscriber_id', $sub->id)
                ->update([
                    'status' => 'unsubscribed',
                    'unsubscribed_at' => now(),
                    'updated_at' => now(),
                ]);

            $log = CampaignTrackingLog::where('message_id', $messageId)->first();
            CampaignUnsubscribeLog::create([
                'tracking_log_id' => $log?->id,
                'subscriber_id' => $sub->id,
                'ip' => request()->ip(),
                'reason' => 'one-click',
            ]);

            if ($log && $log->campaign_id && $campaign = Campaign::find($log->campaign_id)) {
                WebhookDispatcher::emit($campaign, 'unsubscribed', [
                    'subscriber_uid' => $sub->uid,
                    'email' => $sub->email,
                    'message_id' => $messageId,
                ]);
            }
        }

        return response('Has sido dado de baja correctamente.', 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
