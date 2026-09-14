<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentProductBlockade;
use Modules\Document\Entities\DocumentType;
use Modules\Document\Services\PrestashopOrderLookupService;

class RevalidateDocumentTypes extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'documents:revalidate-types
                            {--force : Skip confirmation}
                            {--dry-run : Show changes without applying them}
                            {--limit= : Limit number of documents to process}
                            {--order-id= : Process only a specific order_id}';

    /**
     * The console command description.
     */
    protected $description = 'Revalidate and update document_type_id for all documents based on product blockades';

    /**
     * Caché en memoria de productos por order_id, para no repetir la
     * llamada al bridge si varios documentos comparten pedido.
     *
     * @var array<int, array<int, array{product_id: ?int, attribute_id: ?int}>>
     */
    private array $orderProductsCache = [];

    /**
     * Execute the console command.
     */
    public function handle(PrestashopOrderLookupService $prestashopOrders): int
    {
        try {
            $this->info('🔍 Starting document type revalidation...');
            $this->line('');

            // Get confirmation
            if (! $this->option('force')) {
                if ($this->option('dry-run')) {
                    $this->comment('DRY RUN MODE - No changes will be made');
                } else {
                    if (! $this->confirm('This will update document_type_id for documents based on product blockades. Continue?')) {
                        return Command::SUCCESS;
                    }
                }
            }

            $this->line('');

            // Step 1: Load blockades
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📍 STEP 1: Loading product blockades');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('');

            $blockades = $this->loadBlockades();
            $this->info("✓ Loaded {$blockades['total']} blockade records");
            $this->line('');

            // Step 2: Process documents (los productos de cada pedido se piden
            // al bridge de PrestaShop bajo demanda, por documento — antes se
            // precargaban todos de golpe con una consulta directa a la BD)
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📍 STEP 2: Processing documents');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('');

            $stats = $this->processDocuments($blockades, $prestashopOrders);

            // Summary
            $this->line('');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('✅ PROCESSING COMPLETE');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('');

            $this->info('📊 Results:');
            $this->info("  ✓ Documents Updated: {$stats['updated']}");
            $this->warn("  ⊘ Skipped (no order): {$stats['skipped_no_order']}");
            $this->warn("  ⊘ Skipped (no products): {$stats['skipped_no_products']}");
            $this->warn("  ⊘ Skipped (no blockades): {$stats['skipped_no_blockades']}");
            $this->warn("  ⊘ Skipped (same type): {$stats['skipped_same_type']}");
            $this->warn("  ⊘ Errors: {$stats['errors']}");
            $this->info("  Total Processed: {$stats['total']}");

            $this->line('');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Load all product blockades into memory for fast lookup
     */
    private function loadBlockades(): array
    {
        $blockades = DocumentProductBlockade::with('documentType')
            ->select('product_id', 'product_attribute_id', 'document_type_id', 'blockade_type')
            ->get();

        $blockadeMap = [];
        $blockadesByProduct = []; // Group blockades by product for priority selection

        foreach ($blockades as $blockade) {
            $productId = $blockade->product_id;
            $attributeId = $blockade->product_attribute_id;
            $typeId = $blockade->document_type_id;

            // Create lookup keys
            if ($attributeId === null || $attributeId == 0) {
                // Product-level blockade
                $key = "prod:{$productId}";
                if (! isset($blockadesByProduct[$key])) {
                    $blockadesByProduct[$key] = [];
                }
                $blockadesByProduct[$key][] = [
                    'type_id' => $typeId,
                    'blockade_type' => $blockade->blockade_type,
                    'is_attribute' => false,
                ];
            } else {
                // Attribute/combination-level blockade
                $key = "attr:{$attributeId}";
                if (! isset($blockadesByProduct[$key])) {
                    $blockadesByProduct[$key] = [];
                }
                $blockadesByProduct[$key][] = [
                    'type_id' => $typeId,
                    'blockade_type' => $blockade->blockade_type,
                    'is_attribute' => true,
                ];
            }
        }

        return [
            'total' => $blockades->count(),
            'by_product' => $blockadesByProduct,
        ];
    }

    /**
     * Productos de un pedido vía el bridge de PrestaShop, con caché en
     * memoria para esta ejecución del comando.
     *
     * @return array<int, array{product_id: ?int, attribute_id: ?int}>
     */
    private function productsForOrder(PrestashopOrderLookupService $prestashopOrders, int $orderId): array
    {
        if (isset($this->orderProductsCache[$orderId])) {
            return $this->orderProductsCache[$orderId];
        }

        $order = $prestashopOrders->find($orderId);

        $products = collect($order['products'] ?? [])
            ->map(fn (array $line) => [
                'product_id' => $line['product_id'],
                'attribute_id' => $line['product_attribute_id'],
            ])
            ->all();

        return $this->orderProductsCache[$orderId] = $products;
    }

    /**
     * Process all documents and update their type_id based on blockades
     */
    private function processDocuments(array $blockades, PrestashopOrderLookupService $prestashopOrders): array
    {
        $stats = [
            'total' => 0,
            'updated' => 0,
            'skipped_no_order' => 0,
            'skipped_no_products' => 0,
            'skipped_no_blockades' => 0,
            'skipped_same_type' => 0,
            'errors' => 0,
        ];

        // Build query
        $query = Document::whereNotNull('order_id')
            ->select('id', 'order_id', 'uid', 'type_id')
            ->orderBy('id');

        // Filter by specific order if provided
        if ($orderId = $this->option('order-id')) {
            $query->where('order_id', $orderId);
        }

        // Apply limit if provided
        if ($limit = $this->option('limit')) {
            $query->limit($limit);
        }

        $documents = $query->get();

        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();

        foreach ($documents as $document) {
            $stats['total']++;

            try {
                // Skip if no order_id
                if (! $document->order_id) {
                    $stats['skipped_no_order']++;
                    $bar->advance();

                    continue;
                }

                // Skip if order has no products in PrestaShop
                $orderProducts = $this->productsForOrder($prestashopOrders, (int) $document->order_id);
                if (empty($orderProducts)) {
                    $stats['skipped_no_products']++;
                    $bar->advance();

                    continue;
                }

                // Determine correct document_type_id based on product blockades
                $correctTypeId = $this->determineDocumentType(
                    $orderProducts,
                    $blockades['by_product']
                );

                // Skip if no blockades found for this order's products
                if ($correctTypeId === null) {
                    $stats['skipped_no_blockades']++;
                    $bar->advance();

                    continue;
                }

                // Skip if document already has correct type
                if ($document->type_id == $correctTypeId) {
                    $stats['skipped_same_type']++;
                    $bar->advance();

                    continue;
                }

                // Update document type
                if (! $this->option('dry-run')) {
                    $document->type_id = $correctTypeId;
                    $document->save();
                }

                $stats['updated']++;

                // Log change
                if ($this->option('dry-run') || $this->option('verbose')) {
                    $this->newLine();
                    $this->comment("Document {$document->uid} (Order {$document->order_id}): {$document->type_id} → {$correctTypeId}");
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $this->newLine();
                $this->error("Error processing document {$document->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $stats;
    }

    /**
     * Determine the correct document_type_id based on product blockades
     * Strategy: Use the first blockade found (or implement priority logic)
     */
    private function determineDocumentType(array $orderProducts, array $blockadesByProduct): ?int
    {
        $foundTypeIds = [];

        foreach ($orderProducts as $product) {
            $productId = $product['product_id'];
            $attributeId = $product['attribute_id'];

            // Check attribute-level blockade first (more specific)
            if ($attributeId && $attributeId != 0) {
                $attrKey = "attr:{$attributeId}";
                if (isset($blockadesByProduct[$attrKey])) {
                    foreach ($blockadesByProduct[$attrKey] as $blockade) {
                        $foundTypeIds[] = $blockade['type_id'];
                    }
                }
            }

            // Check product-level blockade (general)
            $prodKey = "prod:{$productId}";
            if (isset($blockadesByProduct[$prodKey])) {
                foreach ($blockadesByProduct[$prodKey] as $blockade) {
                    $foundTypeIds[] = $blockade['type_id'];
                }
            }
        }

        // No blockades found
        if (empty($foundTypeIds)) {
            return null;
        }

        // Strategy: Return the first found (or implement priority logic)
        // TODO: You could implement priority based on DocumentType priority field
        return $foundTypeIds[0];
    }
}
