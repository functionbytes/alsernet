<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Supplier\Entities\SupplierGroup;
use Modules\Supplier\Entities\SupplierProduct;

class SyncProducts extends Command
{
    protected $signature = 'erp:sync-products
        {--full : Full sync (ignore timestamps)}
        {--chunk=100 : Chunk size for processing}
        {--dry-run : Run without making changes}
        {--only-active : Sync only active products}';

    protected $description = 'Sync ERP products (ARTICULO table)';

    private $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public function handle()
    {
        $this->info('🔄 Starting ERP Products Sync...');
        $this->newLine();

        $fullSync = $this->option('full');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');
        $onlyActive = $this->option('only-active');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
        }

        try {
            $this->syncProducts($fullSync, $chunkSize, $dryRun, $onlyActive);
            $this->printSummary();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Fatal error: '.$e->getMessage());
            \Log::error('ERP Products Sync Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    private function syncProducts(bool $fullSync, int $chunkSize, bool $dryRun, bool $onlyActive): void
    {
        $this->info('📦 Syncing Products (ARTICULO)...');

        $query = Articulo::query();

        if ($onlyActive) {
            $query->where('estado', 1);
        }

        if (! $fullSync) {
            $query->where(function ($q) {
                $q->doesntHave('supplierProducts')
                    ->orWhereHas('supplierProducts', function ($sq) {
                        $sq->whereColumn('articulo.fmodificacion', '>', 'supplier_products.last_synced_at');
                    });
            });
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunkSize, function ($products) use ($bar, $dryRun) {
            foreach ($products as $product) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($product) {
                            $this->syncProduct($product);
                        });
                    } else {
                        $this->stats['skipped']++;
                    }
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    \Log::error('Error syncing product', [
                        'erp_id' => $product->idarticulo,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function syncProduct($oracleProduct): void
    {
        $checksum = md5(json_encode([
            'codigo' => $oracleProduct->codigo,
            'descripcion' => $oracleProduct->descripcion,
            'estado' => $oracleProduct->estado,
        ]));

        $existing = SupplierProduct::where('erp_product_id', $oracleProduct->idarticulo)->first();

        // Find group
        $group = null;
        if ($oracleProduct->idgrupo_cl) {
            $group = SupplierGroup::where('erp_group_id', $oracleProduct->idgrupo_cl)->first();
        }

        $data = [
            'erp_product_id' => $oracleProduct->idarticulo,
            'group_id' => $group?->id,
            'erp_group_id' => $oracleProduct->idgrupo_cl,
            'code' => $oracleProduct->codigo,
            'barcode' => $oracleProduct->codbar,
            'name' => $oracleProduct->descripcion,
            'reference' => $oracleProduct->referencia,
            'erp_brand_id' => $oracleProduct->idmarca,
            'brand_name' => $oracleProduct->marca?->descripcion,
            'erp_model_id' => $oracleProduct->idmodelo,
            'model_name' => $oracleProduct->modelo?->descripcion,
            'average_cost' => $oracleProduct->preciomedio,
            'last_purchase_cost' => $oracleProduct->precioultcompra,
            'last_purchase_date' => $oracleProduct->fprecioultcompra,
            'recommended_price' => $oracleProduct->preciorecomendadoprov,
            'length' => $oracleProduct->largo,
            'width' => $oracleProduct->ancho,
            'height' => $oracleProduct->alto,
            'weight' => $oracleProduct->peso,
            'units_per_box' => $oracleProduct->unidadesporcaja,
            'max_serve' => $oracleProduct->maxservir,
            'available_from' => $oracleProduct->fdisponibilidad,
            'allow_reservation' => (bool) $oracleProduct->permitir_reservar,
            'auto_availability' => (bool) $oracleProduct->disponibilidad_automatica,
            'is_active' => (bool) $oracleProduct->estado,
            'is_external' => (bool) $oracleProduct->externo,
            'is_second_hand' => (bool) $oracleProduct->segundamano,
            'requires_serial' => (bool) $oracleProduct->pedir_numero_serie,
            'centralized_purchase' => (bool) $oracleProduct->compra_centralizada,
            'image_path' => $oracleProduct->rutaimagen,
            'notes' => $oracleProduct->observaciones,
            'purchase_notes' => $oracleProduct->observaciones_compras,
            'metadata' => [
                'checksum' => $checksum,
                'ean_interno' => $oracleProduct->ean_interno,
                'intrastat' => $oracleProduct->intrastat,
            ],
            'erp_created_at' => $oracleProduct->fcreacion,
            'erp_updated_at' => $oracleProduct->fmodificacion,
            'erp_deleted_at' => $oracleProduct->fbaja,
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
            SupplierProduct::create($data);
            $this->stats['created']++;
        }
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        $this->info('📊 SYNC SUMMARY - ERP PRODUCTS');
        $this->info('═══════════════════════════════════════════════════');
        $this->line("  ✅ Created:  {$this->stats['created']}");
        $this->line("  🔄 Updated:  {$this->stats['updated']}");
        $this->line("  ⏭️  Skipped:  {$this->stats['skipped']}");
        $this->line("  ❌ Errors:   {$this->stats['errors']}");
        $this->info('═══════════════════════════════════════════════════');
    }
}
