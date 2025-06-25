<?php

namespace App\Console\Commands;

use App\Services\Return\ComponentService;
use App\Models\ProductComponent;
use Illuminate\Console\Command;

class ProcessComponents extends Command
{
    protected $signature = 'components:process
                           {--check-stock : Verificar niveles de stock}
                           {--optimize : Optimizar asignaciones}
                           {--reorder : Generar órdenes de reposición}';

    protected $description = 'Procesar componentes: stock, optimizaciones, reorden';

    protected $componentService;

    public function __construct(ComponentService $componentService)
    {
        parent::__construct();
        $this->componentService = $componentService;
    }

    public function handle()
    {
        $this->info('Iniciando procesamiento de componentes...');

        if ($this->option('check-stock')) {
            $this->checkStockLevels();
        }

        if ($this->option('optimize')) {
            $this->optimizeAllocations();
        }

        if ($this->option('reorder')) {
            $this->generateReorderSuggestions();
        }

        if (!$this->option('check-stock') && !$this->option('optimize') && !$this->option('reorder')) {
            // Ejecutar todas las tareas por defecto
            $this->checkStockLevels();
            $this->optimizeAllocations();
            $this->generateReorderSuggestions();
        }

        $this->info('Procesamiento de componentes completado.');
        return 0;
    }

    protected function checkStockLevels()
    {
        $this->info('Verificando niveles de stock...');

        $lowStockComponents = ProductComponent::active()->lowStock()->count();
        $reorderComponents = ProductComponent::active()->needsReorder()->count();

        $this->line("Componentes con stock bajo: {$lowStockComponents}");
        $this->line("Componentes que necesitan reorden: {$reorderComponents}");

        if ($lowStockComponents > 0) {
            $this->warn("Hay {$lowStockComponents} componentes con stock bajo que requieren atención.");
        }
    }

    protected function optimizeAllocations()
    {
        $this->info('Optimizando asignaciones de stock...');

        $results = $this->componentService->optimizeStockAllocations();

        $this->line("Órdenes procesadas: {$results['orders_processed']}");
        $this->line("Componentes reservados: {$results['components_reserved']}");

        if ($results['components_reserved'] > 0) {
            $this->info("Se reservaron {$results['components_reserved']} componentes adicionales.");
        }
    }

    protected function generateReorderSuggestions()
    {
        $this->info('Generando sugerencias de reorden...');

        $report = $this->componentService->generateInventoryReport();
        $reorderItems = $report['reorder_items'];

        if (empty($reorderItems)) {
            $this->info('No hay componentes que requieran reorden en este momento.');
            return;
        }

        $this->line("Componentes que requieren reorden: " . count($reorderItems));

        $this->table(
            ['Código', 'Nombre', 'Stock Actual', 'Punto Reorden', 'Proveedor', 'Cantidad Sugerida'],
            array_map(function ($item) {
                return [
                    $item['code'],
                    $item['name'],
                    $item['current_stock'],
                    $item['reorder_point'],
                    $item['supplier_name'] ?? 'N/A',
                    $item['suggested_order_quantity'],
                ];
            }, array_slice($reorderItems, 0, 10)) // Mostrar solo los primeros 10
        );

        if (count($reorderItems) > 10) {
            $this->line('... y ' . (count($reorderItems) - 10) . ' componentes más.');
        }
    }
}
