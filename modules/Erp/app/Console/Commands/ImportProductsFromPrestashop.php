<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Erp\Models\ProductImport;
use Modules\Erp\Models\ProductImportTag;
use Modules\Prestashop\Entities\Combination;
use Modules\Prestashop\Entities\Product;

class ImportProductsFromPrestashop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:products
                            {--type=all : Type of import (all, simple, combinations)}
                            {--sync : Sync back to Prestashop after import}
                            {--limit= : Limit number of records to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from Prestashop aalv_combinacionunica_import and aalv_combinaciones_import to polymorphic product_imports table';

    protected int $simpleImported = 0;

    protected int $combinationsImported = 0;

    protected int $errors = 0;

    protected int $tagsProcessed = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $limit = $this->option('limit');

        $this->info('🚀 Iniciando importación de productos desde Prestashop...');
        $this->newLine();

        if ($type === 'all' || $type === 'simple') {
            $this->importSimpleProducts($limit);
        }

        if ($type === 'all' || $type === 'combinations') {
            $this->importCombinations($limit);
        }

        $this->newLine();
        $this->info('✅ Importación completada:');
        $this->table(
            ['Tipo', 'Cantidad'],
            [
                ['Productos simples', $this->simpleImported],
                ['Combinaciones', $this->combinationsImported],
                ['Etiquetas procesadas', $this->tagsProcessed],
                ['Errores', $this->errors],
                ['Total importado', $this->simpleImported + $this->combinationsImported],
            ]
        );

        if ($this->option('sync')) {
            $this->newLine();
            $this->syncBackToPrestashop();
        }

        // Mostrar estadísticas de etiquetas
        $this->showTagsStatistics();

        return Command::SUCCESS;
    }

    /**
     * Mostrar estadísticas de etiquetas
     */
    protected function showTagsStatistics(): void
    {
        $this->newLine();
        $this->info('📊 Estadísticas de Etiquetas:');

        $topTags = ProductImportTag::orderBy('products_count', 'desc')
            ->limit(10)
            ->get(['name', 'products_count']);

        if ($topTags->isNotEmpty()) {
            $this->table(
                ['Etiqueta', 'Productos'],
                $topTags->map(fn ($tag) => [$tag->name, $tag->products_count])->toArray()
            );
        } else {
            $this->warn('No se encontraron etiquetas.');
        }

        $totalUniqueTags = ProductImportTag::count();
        $this->info("Total de etiquetas únicas: {$totalUniqueTags}");
    }

    /**
     * Import simple products from aalv_combinacionunica_import
     */
    protected function importSimpleProducts(?int $limit = null): void
    {
        $this->info('📦 Importando productos simples...');

        $query = DB::connection('prestashop')
            ->table('aalv_combinacionunica_import');

        if ($limit) {
            $query->limit($limit);
        }

        $simpleProducts = $query->get();
        $bar = $this->output->createProgressBar($simpleProducts->count());
        $bar->start();

        foreach ($simpleProducts as $item) {
            try {
                $productImport = ProductImport::updateOrCreate(
                    [
                        'importable_type' => Product::class,
                        'importable_id' => $item->id_product,
                    ],
                    [
                        'id_origen' => $item->id_origen,
                        'id_articulo' => $item->id_articulo,
                        'unidades_oferta' => $item->unidades_oferta,
                        'etiqueta' => $item->etiqueta,
                        'estado_gestion' => $item->estado_gestion,
                        'activo' => $item->activo,
                        'es_segunda_mano' => $item->es_segunda_mano,
                        'externo_disponibilidad' => $item->externo_disponibilidad,
                        'codigo_proveedor' => $item->codigo_proveedor,
                        'precio_costo_proveedor' => $item->precio_costo_proveedor,
                        'tarifa_proveedor' => $item->tarifa_proveedor,
                        'es_arma' => $item->es_arma,
                        'es_arma_fogueo' => $item->es_arma_fogueo,
                        'es_cartucho' => $item->es_cartucho,
                        'ean' => $item->ean,
                        'upc' => $item->upc,
                        'categoria' => $item->categoria,
                        'familia' => $item->familia,
                        'subfamilia' => $item->subfamilia,
                        'grupo' => $item->grupo,
                    ]
                );

                // Procesar etiquetas
                if (! empty($item->etiqueta)) {
                    $productImport->syncTagsFromEtiqueta();
                    $this->tagsProcessed += $productImport->tags()->count();
                }

                $this->simpleImported++;
            } catch (\Exception $e) {
                $this->errors++;
                $this->error("Error importing product {$item->id_product}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Import combinations from aalv_combinaciones_import
     */
    protected function importCombinations(?int $limit = null): void
    {
        $this->info('🔧 Importando combinaciones...');

        $query = DB::connection('prestashop')
            ->table('aalv_combinaciones_import');

        if ($limit) {
            $query->limit($limit);
        }

        $combinations = $query->get();
        $bar = $this->output->createProgressBar($combinations->count());
        $bar->start();

        foreach ($combinations as $item) {
            try {
                $productImport = ProductImport::updateOrCreate(
                    [
                        'importable_type' => Combination::class,
                        'importable_id' => $item->id_product_attribute,
                    ],
                    [
                        'id_origen' => $item->id_origen,
                        'id_articulo' => $item->id_articulo,
                        'unidades_oferta' => $item->unidades_oferta,
                        'etiqueta' => $item->etiqueta,
                        'estado_gestion' => $item->estado_gestion,
                        'activo' => $item->activo,
                        'es_segunda_mano' => $item->es_segunda_mano,
                        'externo_disponibilidad' => $item->externo_disponibilidad,
                        'codigo_proveedor' => $item->codigo_proveedor,
                        'precio_costo_proveedor' => $item->precio_costo_proveedor,
                        'tarifa_proveedor' => $item->tarifa_proveedor,
                        'es_arma' => $item->es_arma,
                        'es_arma_fogueo' => $item->es_arma_fogueo,
                        'es_cartucho' => $item->es_cartucho,
                        'ean' => $item->ean,
                        'upc' => $item->upc,
                        'categoria' => $item->categoria,
                        'familia' => $item->familia,
                        'subfamilia' => $item->subfamilia,
                        'grupo' => $item->grupo,
                    ]
                );

                // Procesar etiquetas
                if (! empty($item->etiqueta)) {
                    $productImport->syncTagsFromEtiqueta();
                    $this->tagsProcessed += $productImport->tags()->count();
                }

                $this->combinationsImported++;
            } catch (\Exception $e) {
                $this->errors++;
                $this->error("Error importing combination {$item->id_product_attribute}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Sync back to Prestashop
     */
    protected function syncBackToPrestashop(): void
    {
        $this->info('🔄 Sincronizando de vuelta a Prestashop...');

        $pendingSync = ProductImport::pendingSync()->get();

        if ($pendingSync->isEmpty()) {
            $this->info('No hay registros pendientes de sincronización.');

            return;
        }

        $bar = $this->output->createProgressBar($pendingSync->count());
        $bar->start();

        $synced = 0;
        $failed = 0;

        foreach ($pendingSync as $import) {
            if ($import->syncToPrestashop()) {
                $synced++;
            } else {
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("✅ Sincronizados: {$synced}");
        if ($failed > 0) {
            $this->warn("⚠️  Fallidos: {$failed}");
        }
    }
}
