<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentProductBlockade;
use Modules\Document\Entities\DocumentSource;
use Modules\Document\Entities\DocumentStatus;
use Modules\Document\Entities\DocumentType;

class CreateBlockedProductDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-blocked-product-documents {--force : Skip confirmation} {--limit= : Limit number of orders to process} {--start-after= : Start processing after this order_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create documents for orders with blocked products based on product blockade rules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Confirmation
        if (! $this->option('force')) {
            if (! $this->confirm('This will create new documents for orders with blocked products. Continue?')) {
                return 0;
            }
        }

        try {
            $this->info('🔄 Starting blocked product document creation...');
            $this->line('');

            // Get the last order_id from documents or start from provided option
            $lastOrderId = $this->getLastOrderId();

            if ($this->option('start-after')) {
                $lastOrderId = (int) $this->option('start-after');
                $this->info("Starting after order ID: {$lastOrderId}");
            } else {
                $this->info("Last registered order ID: {$lastOrderId}");
            }

            $this->line('');

            // Process documents
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📍 Processing Prestashop orders for blocked products');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('');

            $stats = $this->processOrdersWithBlockedProducts($lastOrderId);

            // Summary
            $this->line('');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('✅ PROCESSING COMPLETE');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('');

            $this->info('📊 Results:');
            $this->info("  ✓ Documents Created: {$stats['created']}");
            $this->warn("  ⊘ Skipped (no blockade): {$stats['skipped_no_blockade']}");
            $this->warn("  ⊘ Skipped (already exists): {$stats['skipped_exists']}");
            $this->warn("  ⊘ Skipped (errors): {$stats['errors']}");
            $this->info("  Total Processed: {$stats['total']}");

            $this->line('');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Processing failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }

    /**
     * Get the last registered order_id from documents table
     */
    private function getLastOrderId(): int
    {
        $lastDocument = Document::whereNotNull('order_id')
            ->orderBy('order_id', 'desc')
            ->first();

        return $lastDocument?->order_id ?? 0;
    }

    /**
     * Process orders with blocked products from Prestashop
     */
    private function processOrdersWithBlockedProducts(int $lastOrderId): array
    {
        try {
            // Get all Prestashop orders after the last order_id
            $prestashopOrders = $this->fetchPrestashopOrdersAfterOrderId($lastOrderId);

            $total = count($prestashopOrders);

            if ($total === 0) {
                $this->warn('No new orders found in Prestashop');

                return [
                    'created' => 0,
                    'skipped_no_blockade' => 0,
                    'skipped_exists' => 0,
                    'errors' => 0,
                    'total' => 0,
                ];
            }

            // Apply limit if specified
            if ($this->option('limit')) {
                $limit = (int) $this->option('limit');
                $prestashopOrders = array_slice($prestashopOrders, 0, $limit);
                $this->info("Limiting to {$limit} orders");
            }

            $this->info("Found {$total} new orders to process");
            $this->line('');

            $bar = $this->output->createProgressBar(count($prestashopOrders));
            $bar->start();

            $created = 0;
            $skippedNoBlockade = 0;
            $skippedExists = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($prestashopOrders as $order) {
                try {
                    // Check if document already exists for this order
                    $existingDocument = Document::where('order_id', $order['id_order'])->first();

                    if ($existingDocument) {
                        $skippedExists++;
                        $bar->advance();

                        continue;
                    }

                    // Get products for this order
                    $orderProducts = $this->fetchPrestashopOrderProducts($order['id_order']);

                    if (empty($orderProducts)) {
                        $skippedNoBlockade++;
                        $bar->advance();

                        continue;
                    }

                    // Check if any product has a blockade with type_id
                    $blockadeInfo = $this->getProductBlockadeInfo($orderProducts);

                    if (! $blockadeInfo) {
                        $skippedNoBlockade++;
                        $bar->advance();

                        continue;
                    }

                    // Create document
                    if ($this->createDocument($order, $blockadeInfo, $orderProducts)) {
                        $created++;
                    } else {
                        $errorCount++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Order {$order['id_order']}: {$e->getMessage()}";
                    $errorCount++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            if (! empty($errors)) {
                $this->line('');
                $this->warn('⚠️  Errors encountered:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->error("  • {$error}");
                }
                if (count($errors) > 10) {
                    $this->error('  ... and '.(count($errors) - 10).' more');
                }
            }

            return [
                'created' => $created,
                'skipped_no_blockade' => $skippedNoBlockade,
                'skipped_exists' => $skippedExists,
                'errors' => $errorCount,
                'total' => count($prestashopOrders),
            ];

        } catch (\Exception $e) {
            $this->error('❌ Processing failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get blockade information from order products
     * Returns the blockade info if any product has a blockade, null otherwise
     */
    private function getProductBlockadeInfo(array $products): ?array
    {
        $typeMap = DocumentType::pluck('id', 'slug')->toArray();

        foreach ($products as $product) {
            // Check if this product has a blockade
            $blockades = DocumentProductBlockade::where('product_id', $product['id_product'])
                ->with('documentType')
                ->get();

            foreach ($blockades as $blockade) {
                if ($blockade->document_type_id && $blockade->documentType) {
                    return [
                        'product_id' => $product['id_product'],
                        'product_name' => $product['product_name'],
                        'blockade_type_slug' => $blockade->documentType->slug,
                        'blockade_type_id' => $blockade->document_type_id,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Create a document for an order with blocked products
     */
    private function createDocument(array $order, array $blockadeInfo, array $orderProducts): bool
    {
        try {
            $typeMap = DocumentType::pluck('id', 'slug')->toArray();
            $sourceMap = DocumentSource::pluck('id', 'key')->toArray();
            $statusMap = DocumentStatus::pluck('id', 'key')->toArray();

            $defaultStatusId = $statusMap['awaiting_documents'] ?? 2;

            // Fetch customer data if customer_id exists
            $customerData = [];
            if ($order['id_customer']) {
                try {
                    $customerData = $this->fetchPrestashopCustomer($order['id_customer']);
                } catch (\Exception $e) {
                    // Continue without customer data
                }
            }

            // Generate unique UID
            $uid = $this->generateDocumentUid();

            $documentData = [
                'uid' => $uid,
                'type_id' => $blockadeInfo['blockade_type_id'],
                'source_id' => $sourceMap['prestashop'] ?? 1, // Created from Prestashop orders
                'sync_id' => 2,
                'load_id' => 2,
                'upload_id' => 1,
                'status_id' => $defaultStatusId,
                'validation_status' => 'pending',
                'lang_id' => $order['id_lang'] ?? 1,
                'customer_id' => $order['id_customer'] ?? null,
                'customer_firstname' => $customerData['firstname'] ?? null,
                'customer_lastname' => $customerData['lastname'] ?? null,
                'customer_email' => $customerData['email'] ?? null,
                'customer_dni' => $customerData['vat_number'] ?? null,
                'customer_company' => $customerData['company'] ?? null,
                'order_id' => $order['id_order'],
                'order_reference' => $order['reference'] ?? null,
                'order_date' => $order['date_add'] ?? null,
                'current_stage' => 1,
                'total_stages' => 1,
            ];

            $document = Document::create($documentData);

            // Create document products
            foreach ($orderProducts as $product) {
                try {
                    $document->products()->create([
                        'product_id' => $product['id_product'],
                        'product_name' => strtoupper($product['product_name'] ?? ''),
                        'product_reference' => strtoupper($product['product_reference'] ?? ''),
                        'quantity' => $product['product_quantity'],
                        'price' => $product['product_price'],
                    ]);
                } catch (\Exception $e) {
                    // Log but continue with document creation
                    \Log::warning("Failed to create product for document {$uid}: {$e->getMessage()}");
                }
            }

            return true;

        } catch (\Exception $e) {
            \Log::error("Failed to create document: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Generate unique document UID
     */
    private function generateDocumentUid(): string
    {
        do {
            $uid = 'DOC-'.strtoupper(Str::random(12));
        } while (Document::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Fetch Prestashop orders after a given order_id
     * FILTRADO: Solo obtiene órdenes PAGADAS (cualquier estado pagado en historial)
     * Valida que tengan document_number y document_type
     * Alineado con OrderHistory.php línea 294: if ($new_os->id == 2 || $new_os->paid == 1)
     * METODOLOGÍA: Busca SI EXISTE cualquier estado pagado (no importa si es el último)
     */
    private function fetchPrestashopOrdersAfterOrderId(int $lastOrderId): array
    {
        $config = [
            'host' => env('DB_HOST_PRESTASHOP', '192.168.1.120'),
            'port' => env('DB_PORT_PRESTASHOP', 3306),
            'database' => env('DB_DATABASE_PRESTASHOP', 'alvarez_ana'),
            'username' => env('DB_USERNAME_PRESTASHOP', 'alvarez_ana'),
            'password' => env('DB_PASSWORD_PRESTASHOP', ''),
        ];

        // Query: Obtener órdenes PAGADAS únicamente con datos de documento válidos
        // Usa EXISTS para validar que la orden TUVO estado de pagada en algún momento
        // No importa si ahora está en otro estado (Cancelada, Entregada, etc.)
        // Valida que document_number y document_type no sean NULL ni vacíos
        $query = "SELECT DISTINCT
                    o.id_order,
                    o.id_customer,
                    o.id_lang,
                    o.reference,
                    o.date_add
                  FROM aalv_orders o
                  WHERE o.id_order > {$lastOrderId}
                    AND o.document_number IS NOT NULL
                    AND o.document_number <> ''
                    AND o.document_type IS NOT NULL
                    AND o.document_type <> ''
                    AND EXISTS (
                        SELECT 1
                        FROM aalv_order_history oh
                        WHERE oh.id_order = o.id_order
                          AND oh.id_order_state = 2
                    )
                  ORDER BY o.id_order ASC";

        try {
            $output = shell_exec("mysql -h {$config['host']} -u {$config['username']} -p'{$config['password']}' {$config['database']} -sN -e \"".addslashes($query).'" 2>/dev/null');

            if (! $output) {
                return [];
            }

            $orders = [];
            $lines = explode("\n", trim($output));

            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }

                $parts = explode("\t", $line);
                // Campos esperados: id_order, id_customer, id_lang, reference, date_add
                if (count($parts) >= 5) {
                    $orders[] = [
                        'id_order' => (int) $parts[0],
                        'id_customer' => (int) $parts[1],
                        'id_lang' => (int) $parts[2],
                        'reference' => $parts[3] ?? null,
                        'date_add' => $parts[4] ?? null,
                    ];
                }
            }

            return $orders;
        } catch (\Exception $e) {
            \Log::error('Failed to fetch Prestashop orders: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Fetch Prestashop order products
     */
    private function fetchPrestashopOrderProducts(int $orderId): array
    {
        $config = config('prestashop');

        $query = "SELECT product_id, product_name, product_reference, product_quantity, product_price
                  FROM aalv_order_detail
                  WHERE id_order = {$orderId}";

        try {
            $output = shell_exec("mysql -h {$config['host']} -u {$config['username']} -p'{$config['password']}' {$config['database']} -sN -e \"".addslashes($query).'" 2>/dev/null');

            if (! $output) {
                return [];
            }

            $products = [];
            $lines = explode("\n", trim($output));

            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }

                $parts = explode("\t", $line);
                if (count($parts) >= 5) {
                    $products[] = [
                        'id_product' => (int) $parts[0],
                        'product_name' => $parts[1] ?? null,
                        'product_reference' => $parts[2] ?? null,
                        'product_quantity' => (int) $parts[3],
                        'product_price' => (float) $parts[4],
                    ];
                }
            }

            return $products;
        } catch (\Exception $e) {
            \Log::error("Failed to fetch Prestashop order products for order {$orderId}: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Fetch Prestashop customer data
     */
    private function fetchPrestashopCustomer(int $customerId): ?array
    {
        $config = config('prestashop');

        $query = "SELECT c.firstname, c.lastname, c.email, ca.vat_number, ca.company
                  FROM aalv_customer as c
                  LEFT JOIN aalv_address as ca ON c.id_customer = ca.id_customer AND ca.deleted = 0
                  WHERE c.id_customer = {$customerId}
                  LIMIT 1";

        try {
            $output = shell_exec("mysql -h {$config['host']} -u {$config['username']} -p'{$config['password']}' {$config['database']} -sN -e \"".addslashes($query).'" 2>/dev/null');

            if ($output) {
                $parts = explode("\t", trim($output));
                if (count($parts) >= 2) {
                    return [
                        'firstname' => ! empty($parts[0]) ? $parts[0] : null,
                        'lastname' => ! empty($parts[1]) ? $parts[1] : null,
                        'email' => ! empty($parts[2]) ? $parts[2] : null,
                        'vat_number' => (isset($parts[3]) && ! empty($parts[3])) ? $parts[3] : null,
                        'company' => (isset($parts[4]) && ! empty($parts[4])) ? $parts[4] : null,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error("Failed to fetch Prestashop customer {$customerId}: {$e->getMessage()}");
        }

        return null;
    }
}
