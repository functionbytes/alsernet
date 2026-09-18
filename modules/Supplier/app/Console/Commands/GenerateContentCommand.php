<?php

namespace Modules\Supplier\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Supplier\Jobs\GenerateBulkProductContentJob;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Prompt\Prompt;

class GenerateContentCommand extends Command
{
    protected $signature = 'supplier:generate-content
                            {--limit=3 : Cantidad de productos a procesar}
                            {--prompt-uid= : UID del prompt a usar (opcional)}
                            {--model=gpt-4o-mini : Modelo AI a usar}
                            {--web-search : Habilitar búsqueda web}
                            {--recent : Usar solo productos sincronizados recientemente}';

    protected $description = 'Genera contenido AI para N productos';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $promptUid = $this->option('prompt-uid');
        $model = $this->option('model');
        $webSearch = $this->option('web-search');
        $recent = $this->option('recent');

        // Resolver prompt
        if ($promptUid) {
            $prompt = Prompt::where('uid', $promptUid)->first();
            if (! $prompt) {
                $this->error("Prompt no encontrado: {$promptUid}");

                return Command::FAILURE;
            }
        } else {
            $prompt = Prompt::where('is_active', true)
                ->where('scope', 'global')
                ->orderBy('priority', 'desc')
                ->first();

            if (! $prompt) {
                $this->error('No se encontró un prompt global activo. Usa --prompt-uid.');

                return Command::FAILURE;
            }
        }

        $this->info("Usando prompt: {$prompt->label} ({$prompt->uid})");

        // Obtener productos
        $query = Product::query();

        if ($recent) {
            $query->whereNotNull('last_sync_at')
                ->where('last_sync_at', '>=', now()->subHours(24));
        }

        $products = $query->orderBy('id')->limit($limit)->get();

        if ($products->isEmpty()) {
            $this->warn('No hay productos para procesar.');

            return Command::SUCCESS;
        }

        $batchId = (string) Str::ulid();
        $userId = null;

        foreach ($products as $product) {
            GenerateBulkProductContentJob::dispatch(
                productId: $product->id,
                promptUid: $prompt->uid,
                model: $model,
                webSearch: $webSearch,
                userId: $userId,
                batchId: $batchId,
            );
            $this->info("  → Producto #{$product->id} encolado ({$product->name})");
        }

        $this->newLine();
        $this->info("{$products->count()} producto(s) encolados para generación de contenido.");
        $this->info("Batch ID: {$batchId}");
        $this->info("Modelo: {$model}");
        $this->info('Web search: '.($webSearch ? 'Sí' : 'No'));

        return Command::SUCCESS;
    }
}
