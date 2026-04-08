<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Services\CompetitorTrackingService;

class TrackCompetitorsCommand extends Command
{
    protected $signature = 'reviews:track-competitors';

    protected $description = 'Captura snapshots de rating y resenas de todos los competidores registrados';

    public function __construct(private readonly CompetitorTrackingService $trackingService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $locations = ReviewGoogleLocation::query()->active()->get(['id', 'name']);

        if ($locations->isEmpty()) {
            $this->info('No hay ubicaciones activas.');

            return self::SUCCESS;
        }

        $totalSaved = 0;
        $totalFailed = 0;

        foreach ($locations as $location) {
            try {
                $saved = $this->trackingService->snapshotAll($location->id);
                $totalSaved += $saved;
                $this->line("  Ubicacion [{$location->name}]: {$saved} snapshots guardados.");
            } catch (\Throwable $e) {
                $totalFailed++;
                Log::error('reviews:track-competitors: error procesando ubicacion', [
                    'location_id' => $location->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("  Ubicacion [{$location->name}]: error - {$e->getMessage()}");
            }
        }

        $this->info("Completado: {$totalSaved} snapshots guardados, {$totalFailed} errores.");
        Log::info('reviews:track-competitors completed', ['saved' => $totalSaved, 'failed' => $totalFailed]);

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
