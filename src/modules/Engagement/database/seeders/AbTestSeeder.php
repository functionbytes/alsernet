<?php

namespace Modules\Engagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Engagement\Models\AbTest;
use Modules\Engagement\Models\AbTestVariant;

class AbTestSeeder extends Seeder
{
    public function run(): void
    {
        if (AbTest::query()->exists()) {
            return;
        }

        $test = AbTest::factory()
            ->running()
            ->create([
                'name' => 'CTA principal: descuento vs gratis',
                'description' => 'Compara dos mensajes en el botón principal',
                'sample_size' => 5000,
                'started_at' => now()->subDays(5),
            ]);

        AbTestVariant::factory()
            ->for($test)
            ->create([
                'name' => '20% de descuento',
                'weight' => 50,
                'config' => ['message' => 'Obtén 20% de descuento', 'selector' => '.cta-btn', 'color' => '#90bb13'],
                'impressions' => 2450,
                'conversions' => 180,
            ]);

        AbTestVariant::factory()
            ->for($test)
            ->create([
                'name' => 'Envío gratis',
                'weight' => 50,
                'config' => ['message' => 'Envío gratis en tu primera compra', 'selector' => '.cta-btn', 'color' => '#13C672'],
                'impressions' => 2510,
                'conversions' => 220,
            ]);

        AbTest::factory()
            ->completed()
            ->create([
                'name' => 'Headline versión B',
                'sample_size' => 3000,
                'started_at' => now()->subDays(20),
                'ended_at' => now()->subDays(14),
            ]);
    }
}
