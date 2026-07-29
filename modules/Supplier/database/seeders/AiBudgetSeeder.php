<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Ai\AiBudget;

class AiBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $budgets = [
            ['provider' => 'openai', 'monthly_limit' => 100.00, 'alert_threshold_pct' => 80.00, 'is_active' => true],
            ['provider' => 'anthropic', 'monthly_limit' => 50.00, 'alert_threshold_pct' => 80.00, 'is_active' => true],
        ];

        foreach ($budgets as $data) {
            AiBudget::updateOrCreate(['provider' => $data['provider']], $data);
        }

        $this->command?->info('Seeded '.count($budgets).' AI budgets.');
    }
}
