<?php

namespace Modules\Supplier\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Product\Product;

class BackfillPendingContentCommand extends Command
{
    protected $signature = 'supplier:backfill-pending-content
                            {--dry-run : Mostrar cuántos se crearían sin insertar}
                            {--chunk=200 : Tamaño del chunk de procesamiento}';

    protected $description = 'Crea registros AiContent pending_generation para productos existentes que no tengan uno';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $query = Product::query()
            ->whereNotNull('supplier_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('supplier_ai_contents')
                    ->whereColumn('supplier_ai_contents.supplier_product_id', 'supplier_products.id');
            });

        $total = $query->count();

        if ($total === 0) {
            $this->warn('No hay productos sin contenido asociado.');
            return Command::SUCCESS;
        }

        $this->info("Productos encontrados sin AiContent: {$total}");

        if ($dryRun) {
            $query->select('id', 'name')->limit(20)->get()
                ->each(fn ($p) => $this->line("  → [DRY-RUN] Producto #{$p->id} – {$p->name}"));
            if ($total > 20) {
                $this->line("  … y " . ($total - 20) . " más.");
            }
            return Command::SUCCESS;
        }

        $created = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->select('id', 'supplier_id', 'code', 'erp_id')
            ->chunkById($chunkSize, function ($products) use (&$created, $bar) {
                $rows = $products->map(fn ($product) => [
                    'uid' => (string) Str::ulid(),
                    'supplier_id' => $product->supplier_id,
                    'supplier_product_id' => $product->id,
                    'erp_reference' => $product->code,
                    'status' => AiContent::STATUS_PENDING_GENERATION,
                    'prompt_id' => null,
                    'source_attributes' => json_encode(['id' => $product->erp_id]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                AiContent::insert($rows);
                $created += count($rows);
                $bar->advance(count($rows));
            });

        $bar->finish();
        $this->newLine();
        $this->info("{$created} registros AiContent creados con estado 'pending_generation'.");

        return Command::SUCCESS;
    }
}
