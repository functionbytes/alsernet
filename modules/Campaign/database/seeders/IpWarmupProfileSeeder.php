<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Campaign\Models\IpWarmupProfile;
use Modules\CampaignSendingServers\Models\SendingServer;

/**
 * Genera perfiles de warm-up por defecto (30 días) para cada sending server activo.
 */
class IpWarmupProfileSeeder extends Seeder
{
    public function run(): void
    {
        $servers = SendingServer::whereNull('warmup_completed_at')->get();

        foreach ($servers as $server) {
            $exists = IpWarmupProfile::where('sending_server_id', $server->id)->exists();
            if ($exists) {
                continue;
            }

            $limits = $this->defaultSchedule();
            foreach ($limits as $day => $limit) {
                IpWarmupProfile::create([
                    'sending_server_id' => $server->id,
                    'day_number' => $day,
                    'daily_limit' => $limit,
                ]);
            }

            $server->warmup_started_at = now();
            $server->save();
        }
    }

    /**
     * Schedule estándar de warm-up: empieza en 50/día y escala progresivamente.
     */
    protected function defaultSchedule(): array
    {
        return [
            1 => 50, 2 => 100, 3 => 150, 4 => 200, 5 => 250,
            6 => 300, 7 => 350, 8 => 400, 9 => 450, 10 => 500,
            11 => 600, 12 => 700, 13 => 800, 14 => 900, 15 => 1000,
            16 => 1100, 17 => 1200, 18 => 1300, 19 => 1400, 20 => 1500,
            21 => 1700, 22 => 1900, 23 => 2100, 24 => 2300, 25 => 2500,
            26 => 2750, 27 => 3000, 28 => 3250, 29 => 3500, 30 => 4000,
        ];
    }
}
