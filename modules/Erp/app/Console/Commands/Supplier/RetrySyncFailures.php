<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Entities\SupplierErpProvider;
use Modules\Supplier\Entities\SupplierProduct;
use Modules\Supplier\Entities\SupplierProductPrice;
use Modules\Supplier\Entities\SupplierSyncFailure;
use Modules\Supplier\Services\ErpSyncService;

class RetrySyncFailures extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:retry-sync-failures
                            {--type= : Tipo de sync (price, provider, product). Si no se especifica, reintentar todos}
                            {--max-retries=5 : Número máximo de reintentos permitidos}
                            {--limit=100 : Número máximo de registros a reintentar}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Reintentar sincronizaciones fallidas de Supplier → ERP desde Dead Letter Queue';

    /**
     * Crear instancia del comando
     */
    public function __construct(
        protected ErpSyncService $erpSyncService
    ) {
        parent::__construct();
    }

    /**
     * Ejecutar el comando
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $maxRetries = (int) $this->option('max-retries');
        $limit = (int) $this->option('limit');

        $this->info('🔄 Reintentar sincronizaciones fallidas de Supplier → ERP');
        $this->newLine();

        // Construir query para obtener fallos que se pueden reintentar
        $query = SupplierSyncFailure::where('retry_count', '<', $maxRetries)
            ->orderByDesc('last_retry_at')
            ->orderByDesc('created_at')
            ->limit($limit);

        // Filtrar por tipo si se especifica
        if ($type) {
            $query->where('sync_type', $type);
            $this->info("Filtrando por tipo: <fg=cyan>{$type}</>");
        }

        $failures = $query->get();

        if ($failures->isEmpty()) {
            $this->warn('⚠️  No hay sincronizaciones fallidas que reintentar');

            return self::SUCCESS;
        }

        $this->info("📋 Encontrados <fg=yellow>{$failures->count()}</> registros para reintentar");
        $this->newLine();

        // Barra de progreso
        $bar = $this->output->createProgressBar($failures->count());
        $bar->start();

        $stats = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Procesar cada fallo
        foreach ($failures as $failure) {
            try {
                // Reintentar la sincronización
                $this->retrySync($failure);

                // Remover del Dead Letter Queue si tuvo éxito
                $failure->delete();

                $stats['success']++;

                Log::info('Sync failure retried successfully', [
                    'failure_id' => $failure->id,
                    'sync_type' => $failure->sync_type,
                    'supplier_id' => $failure->supplier_id,
                ]);

            } catch (\Exception $e) {
                // Incrementar contador de reintentos
                $failure->update([
                    'retry_count' => $failure->retry_count + 1,
                    'last_retry_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);

                $stats['failed']++;
                $stats['errors'][] = [
                    'type' => $failure->sync_type,
                    'supplier_id' => $failure->supplier_id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Sync failure retry failed', [
                    'failure_id' => $failure->id,
                    'sync_type' => $failure->sync_type,
                    'supplier_id' => $failure->supplier_id,
                    'error' => $e->getMessage(),
                    'new_retry_count' => $failure->retry_count + 1,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar estadísticas
        $this->displayStats($stats);

        return self::SUCCESS;
    }

    /**
     * Reintentar una sincronización fallida
     *
     * @throws \Exception
     */
    protected function retrySync(SupplierSyncFailure $failure): void
    {
        switch ($failure->sync_type) {
            case 'price':
                $price = SupplierProductPrice::find($failure->supplier_id);
                if (! $price) {
                    throw new \Exception("Price not found: {$failure->supplier_id}");
                }
                $this->erpSyncService->syncPriceToOracle(
                    $price,
                    array_keys($failure->changed_data)
                );
                break;

            case 'provider':
                $provider = SupplierErpProvider::find($failure->supplier_id);
                if (! $provider) {
                    throw new \Exception("Provider not found: {$failure->supplier_id}");
                }
                $this->erpSyncService->syncProviderToOracle(
                    $provider,
                    array_keys($failure->changed_data)
                );
                break;

            case 'product':
                $product = SupplierProduct::find($failure->supplier_id);
                if (! $product) {
                    throw new \Exception("Product not found: {$failure->supplier_id}");
                }
                $this->erpSyncService->syncProductToOracle(
                    $product,
                    array_keys($failure->changed_data)
                );
                break;

            default:
                throw new \Exception("Unknown sync type: {$failure->sync_type}");
        }
    }

    /**
     * Mostrar tabla de estadísticas
     */
    protected function displayStats(array $stats): void
    {
        $this->info('📊 Resultado de reintentos:');
        $this->newLine();

        $this->table(
            ['Resultado', 'Cantidad', 'Porcentaje'],
            [
                [
                    '<fg=green>✅ Exitosos</>',
                    $stats['success'],
                    $stats['success'] > 0 ? round(($stats['success'] / ($stats['success'] + $stats['failed'])) * 100, 1).'%' : '0%',
                ],
                [
                    '<fg=red>❌ Fallidos</>',
                    $stats['failed'],
                    $stats['failed'] > 0 ? round(($stats['failed'] / ($stats['success'] + $stats['failed'])) * 100, 1).'%' : '0%',
                ],
            ]
        );

        // Mostrar detalles de errores si los hay
        if (! empty($stats['errors'])) {
            $this->newLine();
            $this->warn('⚠️  Detalles de errores:');
            $this->newLine();

            foreach ($stats['errors'] as $error) {
                $this->line(sprintf(
                    '  • <fg=red>%s</> (ID: %d): %s',
                    $error['type'],
                    $error['supplier_id'],
                    substr($error['error'], 0, 60).'...'
                ));
            }

            $this->newLine();
            $this->info('💡 Usa el comando <fg=cyan>erp:retry-sync-failures --type={tipo}</>  para reintentar solo un tipo específico');
        }
    }
}
