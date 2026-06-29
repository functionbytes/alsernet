<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Erp\Models\ProductImport;
use Modules\Erp\Models\ProductImportTag;

class ClearProductImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clear
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all product imports and tags from Laravel database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalImports = ProductImport::count();
        $totalTags = ProductImportTag::count();

        $this->warn('⚠️  Esta operación eliminará:');
        $this->line("   - {$totalImports} product imports");
        $this->line("   - {$totalTags} etiquetas");
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('¿Estás seguro de que deseas continuar?')) {
                $this->info('Operación cancelada.');

                return Command::SUCCESS;
            }
        }

        $this->info('🗑️  Eliminando datos...');

        // Deshabilitar foreign key checks temporalmente
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Eliminar product imports y relaciones
        \DB::table('product_import_tag')->truncate();
        \DB::table('product_import_tag_features')->truncate();
        \DB::table('product_imports')->truncate();
        \DB::table('product_import_tags')->truncate();

        // Rehabilitar foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info("✅ {$totalImports} product imports eliminados");
        $this->info("✅ {$totalTags} etiquetas eliminadas");

        $this->newLine();
        $this->info('✅ Limpieza completada exitosamente.');

        return Command::SUCCESS;
    }
}
