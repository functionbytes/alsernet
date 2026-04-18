<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;

class SyncGoogleReviewsCommand extends Command
{
    protected $signature = 'reviews:sync-google {--connection= : Specific connection ID}';

    protected $description = 'Despachar jobs de sincronización de reseñas de Google para ubicaciones activas';

    public function handle(): int
    {
        $connectionId = $this->option('connection');

        $query = ReviewGoogleLocation::query()
            ->active()
            ->where(function ($q) {
                // OAuth locations: must have an active connection
                $q->where('sync_strategy', 'oauth')
                    ->whereHas('connection', fn ($q) => $q->active());
                // Non-OAuth strategies (places_api, serpapi) sync automatically too
                $q->orWhereIn('sync_strategy', ['places_api', 'serpapi']);
            });

        if ($connectionId) {
            $connection = ReviewGoogleConnection::query()->find($connectionId);

            if (! $connection) {
                $this->error("Conexión con ID {$connectionId} no encontrada.");

                return self::FAILURE;
            }

            $query->where('connection_id', $connectionId);
        }

        $locations = $query->get();

        if ($locations->isEmpty()) {
            $this->warn('No se encontraron ubicaciones activas para sincronizar.');

            return self::SUCCESS;
        }

        $dispatched = 0;

        foreach ($locations as $location) {
            SyncGoogleReviewsJob::dispatch($location);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} sync jobs");

        return self::SUCCESS;
    }
}
