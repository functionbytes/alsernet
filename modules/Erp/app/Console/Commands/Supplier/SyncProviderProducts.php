<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Erp\Models\Oracle\Proveedor\Artiprov;
use Modules\Supplier\Entities\SupplierErpProvider;
use Modules\Supplier\Entities\SupplierProduct;
use Modules\Supplier\Entities\SupplierProviderProduct;

class SyncProviderProducts extends Command
{
    protected $signature = 'erp:sync-provider-products
        {--full : Full sync (ignore timestamps)}
        {--chunk=100 : Chunk size for processing}
        {--dry-run : Run without making changes}';

    protected $description = 'Sync ERP provider-products (ARTIPROV table)';

    private $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public function handle()
    {
        $this->info('🔄 Starting ERP Provider-Products Sync...');
        $this->newLine();

        $fullSync = $this->option('full');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
        }

        try {
            $this->syncProviderProducts($fullSync, $chunkSize, $dryRun);
            $this->printSummary();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Fatal error: '.$e->getMessage());
            \Log::error('ERP Provider-Products Sync Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    private function syncProviderProducts(bool $fullSync, int $chunkSize, bool $dryRun): void
    {
        $this->info('📦 Syncing Provider-Products (ARTIPROV)...');

        $query = Artiprov::with(['proveedor', 'articulo']);

        if (! $fullSync) {
            $query->where(function ($q) {
                $q->doesntHave('supplierProviderProducts')
                    ->orWhereHas('supplierProviderProducts', function ($sq) {
                        $sq->whereColumn('artiprov.fmodificacion', '>', 'supplier_provider_products.last_synced_at');
                    });
            });
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunkSize, function ($artiprovs) use ($bar, $dryRun) {
            foreach ($artiprovs as $artiprov) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($artiprov) {
                            $this->syncArtiprov($artiprov);
                        });
                    } else {
                        $this->stats['skipped']++;
                    }
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    \Log::error('Error syncing provider-product', [
                        'erp_id' => $artiprov->idartiprov,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function syncArtiprov($oracleArtiprov): void
    {
        $provider = SupplierErpProvider::where('erp_provider_id', $oracleArtiprov->idproveedor)->first();
        $product = SupplierProduct::where('erp_product_id', $oracleArtiprov->idarticulo)->first();

        if (! $provider) {
            throw new \Exception("Parent provider not found: {$oracleArtiprov->idproveedor}");
        }

        if (! $product) {
            throw new \Exception("Parent product not found: {$oracleArtiprov->idarticulo}");
        }

        $checksum = md5(json_encode([
            'codigo' => $oracleArtiprov->codigo,
            'descripcion' => $oracleArtiprov->descripcion,
            'estado' => $oracleArtiprov->estado,
        ]));

        $existing = SupplierProviderProduct::where('erp_artiprov_id', $oracleArtiprov->idartiprov)->first();

        // Calculate final cost
        $finalCost = $this->calculateFinalCost(
            $oracleArtiprov->pcosto,
            $oracleArtiprov->dto1,
            $oracleArtiprov->dto2
        );

        $data = [
            'erp_artiprov_id' => $oracleArtiprov->idartiprov,
            'provider_id' => $provider->id,
            'product_id' => $product->id,
            'erp_provider_id' => $oracleArtiprov->idproveedor,
            'erp_product_id' => $oracleArtiprov->idarticulo,
            'provider_code' => $oracleArtiprov->codigo,
            'provider_code_alt' => $oracleArtiprov->codigo2,
            'provider_name' => $oracleArtiprov->descripcion,
            'ean13' => $oracleArtiprov->ean13,
            'upc' => $oracleArtiprov->upc,
            'current_cost' => $oracleArtiprov->pcosto,
            'discount1' => $oracleArtiprov->dto1,
            'discount2' => $oracleArtiprov->dto2,
            'final_cost' => $finalCost,
            'price_updated_at' => now(),
            'is_active' => (bool) $oracleArtiprov->estado,
            'is_default' => (bool) $oracleArtiprov->pordefecto,
            'metadata' => ['checksum' => $checksum],
            'erp_created_at' => $oracleArtiprov->fcreacion,
            'erp_updated_at' => $oracleArtiprov->fmodificacion,
            'erp_deleted_at' => $oracleArtiprov->fbaja,
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
            SupplierProviderProduct::create($data);
            $this->stats['created']++;
        }
    }

    private function calculateFinalCost(?float $cost, ?float $discount1, ?float $discount2): float
    {
        if (! $cost) {
            return 0;
        }

        $final = $cost;

        if ($discount1) {
            $final -= ($final * $discount1 / 100);
        }

        if ($discount2) {
            $final -= ($final * $discount2 / 100);
        }

        return round($final, 4);
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        $this->info('📊 SYNC SUMMARY - ERP PROVIDER-PRODUCTS');
        $this->info('═══════════════════════════════════════════════════');
        $this->line("  ✅ Created:  {$this->stats['created']}");
        $this->line("  🔄 Updated:  {$this->stats['updated']}");
        $this->line("  ⏭️  Skipped:  {$this->stats['skipped']}");
        $this->line("  ❌ Errors:   {$this->stats['errors']}");
        $this->info('═══════════════════════════════════════════════════');
    }
}
