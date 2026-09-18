<?php

namespace Modules\Supplier\Console\Commands;

use Illuminate\Console\Command;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Services\Integrations\ErpModelSyncService;

class TestRegisterOnlySync extends Command
{
    protected $signature = 'supplier:test-register-only {--supplier-id=1} {--category-id=1}';

    protected $description = 'Test register_only mode: creates 10 test products and syncs them without generating content';

    public function handle(ErpModelSyncService $syncService)
    {
        $supplierId = (int) $this->option('supplier-id');
        $categoryId = (int) $this->option('category-id');

        $supplier = Supplier::find($supplierId);
        $category = Category::find($categoryId);

        if (!$supplier) {
            $this->error("Supplier #{$supplierId} not found");
            return 1;
        }

        if (!$category) {
            $this->error("Category #{$categoryId} not found");
            return 1;
        }

        $this->info("Testing register_only sync mode...");
        $this->info("Supplier: {$supplier->label} (#{$supplier->id})");
        $this->info("Category: {$category->name} (#{$category->id})");

        // Create 10 test products
        $products = [];
        $this->info("\n📦 Creating 10 test products...");

        for ($i = 1; $i <= 10; $i++) {
            $product = Product::create([
                'supplier_id' => $supplier->id,
                'category_id' => $category->id,
                'erp_id' => 999000 + $i,
                'code' => 'TEST-REGONLY-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name' => "Test Product #{$i} (Register Only)",
                'description' => "Test description for product #{$i}",
            ]);
            $products[] = $product;
            $this->line("  ✓ Created: {$product->name} (ERP ID: {$product->erp_id})");
        }

        // Simulate ERP data for sync
        $this->info("\n🔄 Syncing with register_only=true...");

        $syncedCount = 0;
        $pendingCount = 0;

        foreach ($products as $product) {
            $erpData = [
                'id' => $product->erp_id,
                'description' => $product->name,
                'categorie' => [
                    'id' => $category->erp_id,
                    'name' => $category->name,
                    'sport' => [
                        'id' => $category->sport?->erp_id,
                        'description' => $category->sport?->name,
                    ],
                ],
                'supplier' => [
                    'id' => $supplier->erp_id,
                    'name' => $supplier->label,
                ],
                'product_attributes' => [
                    [
                        'id' => 100001 + $product->erp_id,
                        'ean' => 'EAN-' . $product->erp_id,
                        'name' => $product->name,
                        'subfamily_id' => 1,
                    ],
                ],
            ];

            // Call handleAiContent with registerOnly=true (simulating sync with register_only flag)
            try {
                // Use reflection to call private method
                $reflection = new \ReflectionClass($syncService);
                $method = $reflection->getMethod('handleAiContent');
                $method->setAccessible(true);
                $method->invoke(
                    $syncService,
                    $product,
                    $supplier,
                    $category,
                    $erpData,
                    true  // registerOnly = true
                );
                $syncedCount++;
            } catch (\Exception $e) {
                $this->error("Error syncing {$product->name}: {$e->getMessage()}");
            }
        }

        // Verify that AiContent was created as pending_generation
        $this->info("\n✅ Verification:");

        foreach ($products as $product) {
            $aiContent = AiContent::where('supplier_product_id', $product->id)->first();

            if (!$aiContent) {
                $this->error("  ✗ Product #{$product->id} ({$product->name}): NO AiContent created");
            } elseif ($aiContent->status === AiContent::STATUS_PENDING_GENERATION) {
                $this->line("  ✓ Product #{$product->id} ({$product->name}): Status = PENDING_GENERATION (Correct!)");
                $pendingCount++;
            } elseif ($aiContent->status === AiContent::STATUS_GENERATING) {
                $this->error("  ✗ Product #{$product->id} ({$product->name}): Status = GENERATING (ERROR: Should be PENDING_GENERATION!)");
            } else {
                $this->warn("  ? Product #{$product->id} ({$product->name}): Status = {$aiContent->status}");
            }
        }

        $this->info("\n📊 Summary:");
        $this->line("  Products synced: {$syncedCount}/10");
        $this->line("  Pending generation: {$pendingCount}/10");

        if ($pendingCount === 10) {
            $this->info("\n✨ SUCCESS: All products are in pending_generation state!");
            $this->line("Next steps:");
            $this->line("  1. Go to /panel/setting/suppliers/content");
            $this->line("  2. Find the test products (code: TEST-REGONLY-*)");
            $this->line("  3. Test regeneration and chat functionality");
            $this->line("  4. Run: php artisan supplier:test-register-only --cleanup");
            return 0;
        } else {
            $this->error("\n❌ FAILED: Some products have incorrect status!");
            return 1;
        }
    }
}
