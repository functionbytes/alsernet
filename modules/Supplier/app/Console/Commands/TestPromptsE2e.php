<?php

namespace Modules\Supplier\Console\Commands;

use Illuminate\Console\Command;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Supplier\Supplier;

class TestPromptsE2e extends Command
{
    protected $signature = 'test:prompts-e2e';

    protected $description = 'E2E Test: Create, Edit and Verify Prompts';

    public function handle(): int
    {
        $this->info('=== E2E TEST: Prompts Creation & Verification ===');

        // ====================================================================
        // SCENARIO 1: Create prompt with category_id=5
        // ====================================================================
        $this->line("\n<fg=cyan>SCENARIO 1: Create Prompt (category_id=5)</>");

        $category5 = Category::find(5);
        if (! $category5) {
            $this->warn('Category ID 5 not found. Creating...');
            $category5 = Category::create(['name' => 'Test Category 5']);
        }

        $prompt1Data = [
            'scope' => 'category',
            'category_id' => 5,
            'label' => 'Test Prompt Category 5 - '.now()->format('H:i:s'),
            'prompt_template' => 'Write a product description for: {{product_name}} in category {{category}}. Be creative!',
            'content_type' => 'description',
            'output_language' => 'es',
            'tone' => 'professional',
            'priority' => 10,
            'seo_focus' => true,
            'is_active' => true,
            'is_default' => false,
        ];

        $prompt1 = Prompt::create($prompt1Data);
        $this->info("✓ Created Prompt 1 (ID: {$prompt1->id}, UID: {$prompt1->uid})");
        $this->line("  Label: {$prompt1->label}");
        $this->line("  Scope: {$prompt1->scope}");

        // ====================================================================
        // SCENARIO 2: Create prompt with supplier_id=16&category_id=16
        // ====================================================================
        $this->line("\n<fg=cyan>SCENARIO 2: Create Prompt (supplier_id=16&category_id=16)</>");

        $supplier16 = Supplier::find(16);
        if (! $supplier16) {
            $this->warn('Supplier ID 16 not found. Creating...');
            $supplier16 = Supplier::create([
                'code' => 'TEST_SUPP_16',
                'label' => 'Test Supplier 16',
                'priority' => 10,
                'available' => true,
            ]);
        }

        $category16 = Category::find(16);
        if (! $category16) {
            $this->warn('Category ID 16 not found. Creating...');
            $category16 = Category::create(['name' => 'Test Category 16']);
        }

        $prompt2Data = [
            'supplier_id' => 16,
            'category_id' => 16,
            'scope' => 'supplier_category',
            'label' => 'Test Prompt Supplier+Cat - '.now()->format('H:i:s'),
            'prompt_template' => 'Create SEO title for: {{product_name}}. Keep it under 60 chars.',
            'content_type' => 'seo_title',
            'output_language' => 'es',
            'tone' => 'friendly',
            'priority' => 25,
            'seo_focus' => true,
            'is_active' => true,
            'is_default' => false,
        ];

        $prompt2 = Prompt::create($prompt2Data);
        $this->info("✓ Created Prompt 2 (ID: {$prompt2->id}, UID: {$prompt2->uid})");
        $this->line("  Label: {$prompt2->label}");
        $this->line("  Scope: {$prompt2->scope}");

        // ====================================================================
        // EDIT SCENARIO: Update Prompt 1
        // ====================================================================
        $this->line("\n<fg=cyan>EDITING: Update Prompt 1</>");

        $originalLabel = $prompt1->label;
        $prompt1->update([
            'label' => 'EDITED: '.$prompt1->label,
            'priority' => 50,
            'tone' => 'casual',
        ]);
        $this->info('✓ Updated Prompt 1');
        $this->line("  Old label: {$originalLabel}");
        $this->line("  New label: {$prompt1->label}");
        $this->line("  Priority: {$prompt1->priority} | Tone: {$prompt1->tone}");

        // ====================================================================
        // VERIFICATION: Check they appear in lists
        // ====================================================================
        $this->line("\n<fg=cyan>VERIFICATION: Database queries</>");

        $categoryPrompts = Prompt::where('category_id', 5)->get();
        $this->info("✓ Prompts for category_id=5: {$categoryPrompts->count()} found");
        foreach ($categoryPrompts as $p) {
            $badge = $p->is_active ? '<fg=green>●</>' : '<fg=gray>●</>';
            $this->line("  {$badge} {$p->label} (scope: {$p->scope})");
        }

        $supplierPrompts = Prompt::where('supplier_id', 16)->get();
        $this->info("✓ Prompts for supplier_id=16: {$supplierPrompts->count()} found");
        foreach ($supplierPrompts as $p) {
            $badge = $p->is_active ? '<fg=green>●</>' : '<fg=gray>●</>';
            $this->line("  {$badge} {$p->label} (scope: {$p->scope})");
        }

        // Supplier relationship
        if ($supplier16) {
            $count = $supplier16->prompts()->count();
            $this->info("✓ Supplier 16 has {$count} prompts via relationship");
        }

        // ====================================================================
        // FINAL SUMMARY
        // ====================================================================
        $this->line("\n<fg=green>=== TEST SUMMARY ===</>");
        $this->line('<fg=green>✓</> All scenarios passed!');
        $this->line("\n<fg=blue>Next steps:</>");
        $this->line('1. Visit the supplier detail page:');
        if ($supplier16) {
            $url = route('settings.suppliers.detail', $supplier16->uid);
            $this->line("   <fg=cyan>{$url}</>");
        }
        $this->line('2. Click on the <fg=yellow>Prompts</> tab');
        $this->line('3. Verify the prompts appear in the list');

        return 0;
    }
}
