<?php

namespace App\Console\Commands;

use App\Features\EcommerceFeatures;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

class FeatureFlagsCommand extends Command
{
    protected $signature = 'feature:status {feature? : Nombre del feature flag a consultar}';

    protected $description = 'Lista el estado actual de feature flags';

    private const ALL_FEATURES = [
        EcommerceFeatures::AI_DESCRIPTIONS,
        EcommerceFeatures::ADVANCED_RECOMMENDATIONS,
        EcommerceFeatures::CHATBOT_LLM,
        EcommerceFeatures::SUBSCRIPTIONS,
        EcommerceFeatures::BUNDLES,
        EcommerceFeatures::GIFT_CARDS,
        EcommerceFeatures::SAVED_SEARCHES,
        EcommerceFeatures::EXIT_INTENT,
        EcommerceFeatures::VISUAL_SEARCH,
        EcommerceFeatures::MULTI_WAREHOUSE,
    ];

    public function handle(): int
    {
        $specific = $this->argument('feature');

        if ($specific) {
            $this->table(
                ['Feature', 'Status'],
                [[$specific, Feature::active($specific) ? '<fg=green>ON</>' : '<fg=red>OFF</>']],
            );

            return self::SUCCESS;
        }

        $rows = array_map(
            fn ($f) => [$f, Feature::active($f) ? '<fg=green>ON</>' : '<fg=red>OFF</>'],
            self::ALL_FEATURES,
        );

        $this->table(['Feature', 'Status'], $rows);

        return self::SUCCESS;
    }
}
