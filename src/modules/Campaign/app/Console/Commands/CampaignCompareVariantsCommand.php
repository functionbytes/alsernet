<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Services\CampaignVariantSplitter;

class CampaignCompareVariantsCommand extends Command
{
    protected $signature = 'campaign:compare-variants {campaign_uid}';

    protected $description = 'Compara métricas entre variantes A y B de una campaña';

    public function handle(): int
    {
        $campaign = Campaign::where('uid', $this->argument('campaign_uid'))->firstOrFail();
        $splitter = new CampaignVariantSplitter;
        $result = $splitter->compare($campaign);

        $this->table(
            ['Variante', 'Enviados', 'Aperturas', 'Clics', 'Rebotes', 'Open Rate %', 'Click Rate %'],
            [
                ['A', $result['A']['sent'] ?? 0, $result['A']['opens'] ?? 0, $result['A']['clicks'] ?? 0, $result['A']['bounces'] ?? 0, $result['A']['open_rate'] ?? 0, $result['A']['click_rate'] ?? 0],
                ['B', $result['B']['sent'] ?? 0, $result['B']['opens'] ?? 0, $result['B']['clicks'] ?? 0, $result['B']['bounces'] ?? 0, $result['B']['open_rate'] ?? 0, $result['B']['click_rate'] ?? 0],
            ]
        );

        if (isset($result['A'], $result['B'])) {
            $winner = ($result['B']['open_rate'] > $result['A']['open_rate']) ? 'B' : 'A';
            $this->info("Ganador por open rate: Variante {$winner}");
        }

        return self::SUCCESS;
    }
}
