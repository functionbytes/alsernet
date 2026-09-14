<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Erp\Models\Product\Product;

class SyncProducts extends Command
{
    protected $signature = 'products:sync {--per-page=100} {--start-page=1} {--max-pages=} {--update-existing}';

    protected $description = 'Sincronizar productos desde la API externa de gestión';

    private $apiUrl = 'http://223.1.1.227:8000/api/eloquent/articulo';

    private $timeout = 30;

    private $maxRetries = 3;

    private $createdCount = 0;

    private $updatedCount = 0;

    private $skippedCount = 0;

    private $failedCount = 0;

    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de productos...');
        $this->newLine();

        try {
            $perPage = (int) $this->option('per-page');
            $startPage = (int) $this->option('start-page');
            $maxPages = $this->option('max-pages') ? (int) $this->option('max-pages') : null;
            $updateExisting = $this->hasOption('update-existing');

            $totalProcessed = 0;
            $currentPage = $startPage;
            $hasMore = true;

            while ($hasMore) {
                // Validar límite de páginas
                if ($maxPages && ($currentPage - $startPage) >= $maxPages) {
                    $this->info("⛔ Límite de páginas alcanzado ($maxPages)");
                    break;
                }

                $this->info("📄 Procesando página $currentPage (per_page: $perPage)...");

                try {
                    $response = $this->fetchProductsPage($currentPage, $perPage);

                    if (! isset($response['success']) || ! $response['success']) {
                        $this->error("❌ Error en respuesta de la API página $currentPage");
                        $this->failedCount += $perPage;
                        break;
                    }

                    $products = $response['data'] ?? [];
                    $pagination = $response['pagination'] ?? [];

                    if (empty($products)) {
                        $this->warn("⚠️  No hay productos en la página $currentPage");
                        break;
                    }

                    // Procesar productos en transacción
                    DB::transaction(function () use ($products, $updateExisting) {
                        foreach ($products as $apiProduct) {
                            $this->syncProduct($apiProduct, $updateExisting);
                        }
                    });

                    $totalProcessed += count($products);
                    $hasMore = $pagination['has_more'] ?? false;
                    $currentPage++;

                    $this->line("  ✅ Página procesada. Creados: {$this->createdCount}, Actualizados: {$this->updatedCount}, Salteados: {$this->skippedCount}, Errores: {$this->failedCount}");
                    $this->newLine();

                } catch (\Exception $e) {
                    $this->error("❌ Error procesando página $currentPage: ".$e->getMessage());
                    $this->failedCount += $perPage;
                    break;
                }
            }

            $this->printSummary($totalProcessed);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error general en sincronización: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Obtener productos de la API con reintentos
     */
    private function fetchProductsPage(int $page, int $perPage): array
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)->get($this->apiUrl, [
                    'sort_order' => 'asc',
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                $attempt++;
                if ($attempt < $this->maxRetries) {
                    $this->warn("  ⏳ Reintentando página $page (intento $attempt/$this->maxRetries)...");
                    sleep(2); // Esperar 2 segundos antes de reintentar
                }

            } catch (\Exception $e) {
                $attempt++;
                if ($attempt < $this->maxRetries) {
                    $this->warn("  ⏳ Error de conexión. Reintentando (intento $attempt/$this->maxRetries)...");
                    sleep(2);
                } else {
                    $this->error("  ❌ Falló después de $this->maxRetries intentos: ".$e->getMessage());
                }
            }
        }

        return ['success' => false];
    }

    /**
     * Sincronizar un producto individual
     */
    private function syncProduct(array $apiProduct, bool $updateExisting): void
    {
        try {
            $codigo = $apiProduct['codigo'] ?? null;
            $codbar = $apiProduct['codbar'] ?? null;
            $descripcion = $apiProduct['descripcion'] ?? 'Sin descripción';
            $estado = $apiProduct['estado'] ?? false;
            $idarticulo = $apiProduct['idarticulo'] ?? 0;

            // Validar datos mínimos
            if (! $codigo && ! $codbar) {
                $this->skippedCount++;

                return;
            }

            // Buscar producto existente
            $existingProduct = Product::where('reference', $codigo)
                ->orWhere('barcode', $codbar)
                ->first();

            if ($existingProduct) {
                if ($updateExisting) {
                    $existingProduct->update([
                        'title' => $descripcion,
                        'slug' => Str::slug($descripcion),
                        'available' => $estado ? 1 : 0,
                        'management' => $idarticulo,
                    ]);
                    $this->updatedCount++;
                } else {
                    $this->skippedCount++;
                }
            } else {
                // Crear nuevo producto con todos los campos requeridos
                $productData = [
                    'slack' => (string) Str::uuid(),
                    'title' => $descripcion,
                    'slug' => Str::slug($descripcion),
                    'reference' => $codigo,
                    'barcode' => $codbar,
                    'available' => $estado ? 1 : 0,
                    'management' => (int) $idarticulo,
                    'kardex' => 0,
                    'validate' => 0,
                    'count' => 0,
                ];

                Product::create($productData);
                $this->createdCount++;
            }

        } catch (\Exception $e) {
            $this->failedCount++;
            \Log::error('Error sincronizando producto: '.json_encode($apiProduct).' | '.$e->getMessage());
        }
    }

    /**
     * Mostrar resumen de sincronización
     */
    private function printSummary(int $totalProcessed): void
    {
        $this->newLine();
        $this->info('═════════════════════════════════════════════════════');
        $this->info('📊 RESUMEN DE SINCRONIZACIÓN');
        $this->info('═════════════════════════════════════════════════════');
        $this->line("Total procesados:    {$totalProcessed}");
        $this->line("✅ Creados:          {$this->createdCount}");
        $this->line("🔄 Actualizados:     {$this->updatedCount}");
        $this->line("⏭️  Salteados:        {$this->skippedCount}");
        $this->line("❌ Errores:          {$this->failedCount}");
        $this->info('═════════════════════════════════════════════════════');
        $this->newLine();

        if ($this->failedCount > 0) {
            $this->warn('⚠️  Revisa los logs para más detalles de los errores.');
        }
    }
}
