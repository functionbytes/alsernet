<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Erp\Services\EndpointDiscoveryService;

class SyncErpEndpointsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:sync-endpoints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover and sync all ERP endpoints from routes to database';

    /**
     * Execute the console command.
     */
    public function handle(EndpointDiscoveryService $discoveryService): int
    {
        $this->info('🔍 Escaneando rutas de Laravel bajo /api/erp/*...');

        $discovered = $discoveryService->discoverEndpoints();

        $this->newLine();
        $this->info("✅ Encontrados {$discovered->count()} endpoints en /api/erp/*");
        $this->newLine();

        // Get stats before sync
        $statsBefore = $discoveryService->getSyncStatistics();

        // Perform sync
        $discoveryService->syncWithDatabase();

        // Get stats after sync
        $statsAfter = $discoveryService->getSyncStatistics();

        $this->newLine();
        $this->info('📊 Resumen de sincronización:');

        $newEndpoints = $statsAfter['total_in_database'] - $statsBefore['total_in_database'];
        $newDeprecated = $statsAfter['deprecated'] - $statsBefore['deprecated'];

        $this->line("   ✅ Endpoints nuevos: {$newEndpoints}");
        $this->line("   ⚠️  Endpoints deprecated: {$newDeprecated}");
        $this->line("   📝 Endpoints configurados: {$statsAfter['configured']} / {$statsAfter['total_in_database']}");
        $this->line("   ❌ Endpoints sin configurar: {$statsAfter['unconfigured']}");

        $this->newLine();
        $this->info('✅ Sincronización completada');

        return self::SUCCESS;
    }
}
