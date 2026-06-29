<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;
use Modules\Supplier\Entities\SupplierErpProvider;

class SyncProviders extends Command
{
    protected $signature = 'erp:sync-providers
        {--full : Full sync (ignore timestamps)}
        {--chunk=100 : Chunk size for processing}
        {--dry-run : Run without making changes}';

    protected $description = 'Sync ERP providers (PROVEEDOR table)';

    private $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public function handle()
    {
        $this->info('🔄 Starting ERP Providers Sync...');
        $this->newLine();

        $fullSync = $this->option('full');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
        }

        try {
            $this->syncProviders($fullSync, $chunkSize, $dryRun);
            $this->printSummary();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Fatal error: '.$e->getMessage());
            \Log::error('ERP Providers Sync Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    private function syncProviders(bool $fullSync, int $chunkSize, bool $dryRun): void
    {
        $this->info('📦 Syncing Providers (PROVEEDOR)...');

        $query = Proveedor::query();

        if (! $fullSync) {
            $query->where(function ($q) {
                $q->doesntHave('supplierErpProviders')
                    ->orWhereHas('supplierErpProviders', function ($sq) {
                        $sq->whereColumn('proveedor.fmodificacion', '>', 'supplier_erp_providers.last_synced_at');
                    });
            });
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunkSize, function ($providers) use ($bar, $dryRun) {
            foreach ($providers as $provider) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($provider) {
                            $this->syncProvider($provider);
                        });
                    } else {
                        $this->stats['skipped']++;
                    }
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    \Log::error('Error syncing provider', [
                        'erp_id' => $provider->idproveedor,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function syncProvider($oracleProvider): void
    {
        $checksum = md5(json_encode([
            'nombre' => $oracleProvider->nombre,
            'email' => $oracleProvider->email,
            'estado' => $oracleProvider->estado,
        ]));

        $existing = SupplierErpProvider::where('erp_provider_id', $oracleProvider->idproveedor)->first();

        $data = [
            'erp_provider_id' => $oracleProvider->idproveedor,
            'code' => $oracleProvider->codcliente,
            'name' => $oracleProvider->nombre,
            'tax_id' => $oracleProvider->cif,
            'contact_person' => $oracleProvider->percontacto,
            'email' => $oracleProvider->email,
            'phone' => $oracleProvider->telefono1,
            'phone_alt' => $oracleProvider->telefono2,
            'fax' => $oracleProvider->fax,
            'website' => $oracleProvider->web,
            'street' => $oracleProvider->calle,
            'street_number' => $oracleProvider->num,
            'city' => $oracleProvider->localidad,
            'postal_code' => $oracleProvider->cp,
            'province' => $oracleProvider->provincia,
            'country' => $oracleProvider->pais,
            'erp_country_id' => $oracleProvider->idpais,
            'shipping_cost' => $oracleProvider->portes,
            'discount_percent' => $oracleProvider->dto,
            'partial_delivery_days' => $oracleProvider->dias_servir_parcial,
            'partial_delivery_percent' => $oracleProvider->porcentaje_servir_parcial,
            'is_active' => (bool) $oracleProvider->estado,
            'is_visible' => (bool) $oracleProvider->visible,
            'is_creditor' => (bool) $oracleProvider->acreedor,
            'notes' => $oracleProvider->observaciones,
            'shipping_notes' => $oracleProvider->observacionportes,
            'metadata' => ['checksum' => $checksum],
            'erp_created_at' => $oracleProvider->fcreacion,
            'erp_updated_at' => $oracleProvider->fmodificacion,
            'erp_deleted_at' => $oracleProvider->fbaja,
            'last_synced_at' => now(),
        ];

        if ($existing) {
            $oldChecksum = $existing->metadata['checksum'] ?? null;
            if ($oldChecksum !== $checksum) {
                $existing->update($data);
                $this->stats['updated']++;
            } else {
                $existing->touch('last_synced_at');
                $this->stats['skipped']++;
            }
        } else {
            SupplierErpProvider::create($data);
            $this->stats['created']++;
        }
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        $this->info('📊 SYNC SUMMARY - ERP PROVIDERS');
        $this->info('═══════════════════════════════════════════════════');
        $this->line("  ✅ Created:  {$this->stats['created']}");
        $this->line("  🔄 Updated:  {$this->stats['updated']}");
        $this->line("  ⏭️  Skipped:  {$this->stats['skipped']}");
        $this->line("  ❌ Errors:   {$this->stats['errors']}");
        $this->info('═══════════════════════════════════════════════════');
    }
}
