<?php

namespace Modules\HelpdeskCampaigns\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;

/**
 * Decides whether a given visitor should see a campaign right now,
 * based on per-campaign frequency capping rules:
 *  - max_impressions_per_user: hard ceiling on lifetime views per visitor
 *  - cooldown_minutes: minimum time between consecutive views to same visitor
 *
 * Visitor identity is resolved in this priority order:
 *  1. customer_id (logged-in customer)
 *  2. customer_session_id (anonymous session)
 *  3. ip_address (anonymous, no cookie)
 *
 * Hot-path optimization: frequency data is cached to avoid DB queries on every
 * tracking request. The store is configurable via
 * config('helpdeskcampaigns.cache_store') — set it to 'redis' where available;
 * by default the application's default cache store is used so deployments
 * without Redis keep working. Cache is invalidated by RecordImpressionJob
 * after each impression is persisted.
 */
class FrequencyCapService
{
    public function shouldShow(Campaign $campaign, array $visitor): bool
    {
        if ($campaign->status !== 'active') {
            return false;
        }

        if (! $campaign->max_impressions_per_user && ! $campaign->cooldown_minutes) {
            return true;
        }

        $visitorKey = $this->visitorKey($visitor);

        if (! $visitorKey) {
            return true;
        }

        $cacheKey = "hc:cap:{$campaign->id}:{$visitorKey}";
        $ttl = max($campaign->cooldown_minutes ?? 0, 60) * 60;

        $data = $this->cache()->remember($cacheKey, $ttl, fn () => $this->loadFromDb($campaign, $visitor));

        if ($campaign->max_impressions_per_user && $data['count'] >= $campaign->max_impressions_per_user) {
            return false;
        }

        if ($campaign->cooldown_minutes && $data['last']) {
            $lastSeen = Carbon::parse($data['last']);
            if (now()->diffInMinutes($lastSeen) < $campaign->cooldown_minutes) {
                return false;
            }
        }

        return true;
    }

    /**
     * Called by RecordImpressionJob after persisting — invalidates the cap cache
     * so the next request gets a fresh count from DB.
     */
    public function invalidate(int $campaignId, array $visitor): void
    {
        $visitorKey = $this->visitorKey($visitor);

        if ($visitorKey) {
            $this->cache()->forget("hc:cap:{$campaignId}:{$visitorKey}");
        }
    }

    /**
     * Cache store for frequency-cap data. `null` (the default) resolves to the
     * application's default store, so environments without Redis don't break.
     */
    private function cache(): Repository
    {
        return Cache::store(config('helpdeskcampaigns.cache_store'));
    }

    private function loadFromDb(Campaign $campaign, array $visitor): array
    {
        $query = CampaignImpression::query()->where('campaign_id', $campaign->id);
        $this->applyVisitorFilter($query, $visitor);

        // Un solo roundtrip: COUNT + MAX en la misma query (antes eran dos —
        // un SELECT viewed_at ordenado y un COUNT(*) — sobre el mismo filtro).
        $row = $query->selectRaw('COUNT(*) AS impressions, MAX(viewed_at) AS last_viewed_at')->first();

        return [
            'count' => (int) ($row->impressions ?? 0),
            'last' => $row->last_viewed_at ?? null,
        ];
    }

    /**
     * customer_session_id lo envia el cliente en el body del POST — no esta
     * atado a ninguna cookie/sesion real firmada por el servidor, asi que NO
     * puede ser la unica base de la clave: un visitante podia mandar un
     * session_id aleatorio en cada request y saltarse el cap y el dedup por
     * completo (concatenarlo con la IP no lo arregla — el hash sigue
     * cambiando igual con cada session_id nuevo). Para visitantes anonimos
     * la clave se basa solo en IP, que sí controla el servidor; el
     * session_id se sigue registrando en CampaignImpression para reporting,
     * simplemente deja de ser la base de confianza del cap.
     */
    private function visitorKey(array $visitor): ?string
    {
        if (! empty($visitor['customer_id'])) {
            return 'c'.$visitor['customer_id'];
        }

        if (! empty($visitor['ip_address'])) {
            return 'i'.substr(md5($visitor['ip_address']), 0, 12);
        }

        return null;
    }

    private function applyVisitorFilter($query, array $visitor): void
    {
        if (! empty($visitor['customer_id'])) {
            $query->where('customer_id', $visitor['customer_id']);
        } elseif (! empty($visitor['ip_address'])) {
            $query->where('ip_address', $visitor['ip_address']);
        }
    }
}
