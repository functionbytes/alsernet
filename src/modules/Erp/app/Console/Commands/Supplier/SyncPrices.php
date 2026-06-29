<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Erp\Models\Oracle\Proveedor\ArtiprovTarifapro;
use Modules\Supplier\Entities\SupplierProductPrice;
use Modules\Supplier\Entities\SupplierProviderProduct;

class SyncPrices extends Command
{
    protected $signature = 'erp:sync-prices
        {--full : Full sync (ignore timestamps)}
        {--chunk=100 : Chunk size for processing}
        {--dry-run : Run without making changes}
        {--update-current : Mark latest prices as is_current}';

    protected $description = 'Sync ERP product prices (ARTIPROV_TARIFAPRO table) with history';

    private $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'marked_current' => 0,
    ];

    public function handle()
    {
        $this->info('🔄 Starting ERP Prices Sync...');
        $this->newLine();

        $fullSync = $this->option('full');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');
        $updateCurrent = $this->option('update-current');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
        }

        try {
            $this->syncPrices($fullSync, $chunkSize, $dryRun);

            if ($updateCurrent && ! $dryRun) {
                $this->updateCurrentPrices();
            }

            $this->printSummary();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Fatal error: '.$e->getMessage());
            \Log::error('ERP Prices Sync Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    private function syncPrices(bool $fullSync, int $chunkSize, bool $dryRun): void
    {
        $this->info('💰 Syncing Prices (ARTIPROV_TARIFAPRO)...');

        $query = ArtiprovTarifapro::with('artiprov');

        if (! $fullSync) {
            $query->where(function ($q) {
                $q->doesntHave('supplierProductPrices')
                    ->orWhereHas('supplierProductPrices', function ($sq) {
                        $sq->whereColumn('artiprov_tarifapro.fmodificacion', '>', 'supplier_product_prices.last_synced_at');
                    });
            });
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunkSize, function ($prices) use ($bar, $dryRun) {
            foreach ($prices as $price) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($price) {
                            $this->syncPrice($price);
                        });
                    } else {
                        $this->stats['skipped']++;
                    }
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    \Log::error('Error syncing price', [
                        'erp_id' => $price->idartiprov_tarifapro,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function syncPrice($oraclePrice): void
    {
        $providerProduct = SupplierProviderProduct::where('erp_artiprov_id', $oraclePrice->idartiprov)->first();

        if (! $providerProduct) {
            throw new \Exception("Parent provider-product not found: {$oraclePrice->idartiprov}");
        }

        $checksum = md5(json_encode([
            'pcosto' => $oraclePrice->pcosto,
            'dto1' => $oraclePrice->dto1,
            'dto2' => $oraclePrice->dto2,
            'estado' => $oraclePrice->estado,
        ]));

        $existing = SupplierProductPrice::where('erp_price_id', $oraclePrice->idartiprov_tarifapro)->first();

        // Calculate final cost
        $finalCost = $this->calculateFinalCost(
            $oraclePrice->pcosto,
            $oraclePrice->dto1,
            $oraclePrice->dto2
        );

        $data = [
            'erp_price_id' => $oraclePrice->idartiprov_tarifapro,
            'provider_product_id' => $providerProduct->id,
            'erp_artiprov_id' => $oraclePrice->idartiprov,
            'effective_date' => $oraclePrice->fecha,
            'cost' => $oraclePrice->pcosto,
            'discount1' => $oraclePrice->dto1,
            'discount2' => $oraclePrice->dto2,
            'final_cost' => $finalCost,
            'is_active' => (bool) $oraclePrice->estado,
            'is_current' => false, // Set to false by default, will be updated in bulk
            'metadata' => ['checksum' => $checksum],
            'erp_created_at' => $oraclePrice->fcreacion,
            'erp_updated_at' => $oraclePrice->fmodificacion,
            'erp_deleted_at' => $oraclePrice->fbaja,
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
            SupplierProductPrice::create($data);
            $this->stats['created']++;
        }
    }

    private function updateCurrentPrices(): void
    {
        $this->info('🔄 Updating is_current flags for latest prices...');

        // For each provider-product, mark only the latest active price as current
        $providerProducts = SupplierProviderProduct::all();

        foreach ($providerProducts as $providerProduct) {
            // Get the latest active price by effective_date
            $latestPrice = SupplierProductPrice::where('provider_product_id', $providerProduct->id)
                ->where('is_active', true)
                ->orderBy('effective_date', 'desc')
                ->first();

            if ($latestPrice) {
                // Unmark all others
                SupplierProductPrice::where('provider_product_id', $providerProduct->id)
                    ->where('id', '!=', $latestPrice->id)
                    ->update(['is_current' => false]);

                // Mark this one as current
                $latestPrice->update(['is_current' => true]);
                $this->stats['marked_current']++;
            }
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
        $this->info('📊 SYNC SUMMARY - ERP PRICES');
        $this->info('═══════════════════════════════════════════════════');
        $this->line("  ✅ Created:         {$this->stats['created']}");
        $this->line("  🔄 Updated:         {$this->stats['updated']}");
        $this->line("  ⏭️  Skipped:         {$this->stats['skipped']}");
        $this->line("  ❌ Errors:          {$this->stats['errors']}");
        $this->line("  💫 Marked Current:  {$this->stats['marked_current']}");
        $this->info('═══════════════════════════════════════════════════');
    }
}
