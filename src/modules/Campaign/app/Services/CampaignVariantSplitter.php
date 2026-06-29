<?php

namespace Modules\Campaign\Services;

use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignSubscriber;

/**
 * Divide suscriptores entre variantes A/B de forma determinista.
 * Usa hash del email para garantizar que el mismo suscriptor siempre
 * reciba la misma variante si se reenvía.
 */
class CampaignVariantSplitter
{
    public function assignVariant(CampaignSubscriber $subscriber, Campaign $campaign): string
    {
        if (empty($campaign->variant)) {
            return 'A';
        }

        // Si la campaña es hija (variante B), siempre B
        if ($campaign->variant === 'B') {
            return 'B';
        }

        // Campaña padre (variante A) — decidir por hash del email
        $hash = crc32($subscriber->email);

        return ($hash % 2 === 0) ? 'A' : 'B';
    }

    /**
     * Devuelve la campaña activa para un suscriptor dado
     * (la propia A o la hija B según el split).
     */
    public function resolveCampaign(CampaignSubscriber $subscriber, Campaign $campaign): Campaign
    {
        $variant = $this->assignVariant($subscriber, $campaign);

        if ($variant === 'B' && $campaign->parent_campaign_id === null) {
            $child = Campaign::where('parent_campaign_id', $campaign->id)
                ->where('variant', 'B')
                ->first();
            if ($child) {
                return $child;
            }
        }

        return $campaign;
    }

    /**
     * Compara métricas entre variantes A y B.
     */
    public function compare(Campaign $parentCampaign): array
    {
        $child = Campaign::where('parent_campaign_id', $parentCampaign->id)
            ->where('variant', 'B')
            ->first();

        $metrics = ['sent', 'open', 'click', 'bounce'];
        $result = [];

        foreach (['A' => $parentCampaign, 'B' => $child] as $variant => $camp) {
            if (! $camp) {
                $result[$variant] = null;

                continue;
            }

            $sent = max(1, $camp->sent_count);
            $result[$variant] = [
                'sent' => $camp->sent_count,
                'opens' => $camp->open_count,
                'clicks' => $camp->click_count,
                'bounces' => $camp->bounce_count,
                'open_rate' => round(($camp->open_count / $sent) * 100, 2),
                'click_rate' => round(($camp->click_count / $sent) * 100, 2),
            ];
        }

        return $result;
    }
}
