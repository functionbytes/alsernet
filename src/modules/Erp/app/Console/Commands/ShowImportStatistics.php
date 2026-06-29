<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Erp\Models\ProductImport;
use Modules\Erp\Models\ProductImportTag;
use Modules\Prestashop\Entities\Combination;
use Modules\Prestashop\Entities\Product;

class ShowImportStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show statistics of imported products and tags';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Estadísticas de Importación de Productos');
        $this->newLine();

        // Estadísticas generales
        $totalImports = ProductImport::count();
        $simpleProducts = ProductImport::where('importable_type', Product::class)->count();
        $combinations = ProductImport::where('importable_type', Combination::class)->count();
        $active = ProductImport::where('activo', 1)->count();
        $secondHand = ProductImport::where('es_segunda_mano', 1)->count();
        $weapons = ProductImport::where('es_arma', 1)->count();
        $ammunition = ProductImport::where('es_cartucho', 1)->count();

        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total de imports', $totalImports],
                ['Productos simples', $simpleProducts],
                ['Combinaciones', $combinations],
                ['Activos', $active],
                ['Segunda mano', $secondHand],
                ['Armas', $weapons],
                ['Munición', $ammunition],
            ]
        );

        // Estadísticas por estado de gestión
        $this->newLine();
        $this->info('📦 Por Estado de Gestión:');
        $byEstado = ProductImport::selectRaw('estado_gestion, COUNT(*) as total')
            ->groupBy('estado_gestion')
            ->orderBy('total', 'desc')
            ->get();

        if ($byEstado->isNotEmpty()) {
            $this->table(
                ['Estado', 'Cantidad'],
                $byEstado->map(fn ($item) => [$item->estado_gestion, $item->total])->toArray()
            );
        }

        // Estadísticas de etiquetas
        $this->newLine();
        $this->info('🏷️  Top 15 Etiquetas:');
        $topTags = ProductImportTag::orderBy('products_count', 'desc')
            ->limit(15)
            ->get(['name', 'products_count']);

        if ($topTags->isNotEmpty()) {
            $this->table(
                ['Etiqueta', 'Productos'],
                $topTags->map(fn ($tag) => [$tag->name, $tag->products_count])->toArray()
            );
        }

        $totalTags = ProductImportTag::count();
        $this->newLine();
        $this->info("Total de etiquetas únicas: {$totalTags}");

        // Productos sin etiquetas
        $withoutTags = ProductImport::doesntHave('tags')->count();
        $this->info("Productos sin etiquetas: {$withoutTags}");

        // Sincronización pendiente
        $this->newLine();
        $this->info('🔄 Estado de Sincronización:');
        $pendingSync = ProductImport::where('sync_pending', true)->count();
        $synced = ProductImport::whereNotNull('last_sync_at')->count();

        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['Sincronizados', $synced],
                ['Pendientes de sincronización', $pendingSync],
                ['Nunca sincronizados', $totalImports - $synced],
            ]
        );

        return Command::SUCCESS;
    }
}
