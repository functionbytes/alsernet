<?php

namespace Modules\Supplier\Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Supplier\Database\Factories\Ai\AiContentFactory;
use Modules\Supplier\Database\Factories\Characteristic\ErpCharacteristicFactory;
use Modules\Supplier\Database\Factories\Characteristic\ErpCharacteristicValueFactory;
use Modules\Supplier\Database\Factories\Characteristic\ModelCharacteristicFactory;
use Modules\Supplier\Database\Factories\Characteristic\VariantCharacteristicFactory;
use Modules\Supplier\Database\Factories\Product\ProductAttributeFactory;
use Modules\Supplier\Database\Factories\Product\ProductFactory;
use Modules\Supplier\Models\Characteristic\ErpCharacteristic;
use Modules\Supplier\Models\Characteristic\VariantCharacteristic;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Cubre el panel de características ERP (modelo y variante) de un
 * contenido de IA: catálogo, asignaciones existentes, y guardado.
 */
class SupplierContentCharacteristicsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users') || ! Schema::hasTable('permissions')) {
            $this->markTestSkipped('Required tables (users, permissions) are not present in the test database.');
        }

        activity()->disableLogging();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function userWithPermissions(): User
    {
        foreach (['suppliers.view.content', 'suppliers.content.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::factory()->create(['available' => true]);
        $user->givePermissionTo(['suppliers.view.content', 'suppliers.content.manage']);

        return $user;
    }

    public function test_characteristics_panel_returns_catalog_and_current_assignments(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermissions();
        $product = ProductFactory::new()->create();
        $attribute = ProductAttributeFactory::new()->create(['product_id' => $product->id]);
        $content = AiContentFactory::new()->create(['supplier_product_id' => $product->id]);
        $characteristic = ErpCharacteristic::first() ?? ErpCharacteristicFactory::new()->create();
        $value = ErpCharacteristicValueFactory::new()->create(['characteristic_id' => $characteristic->id]);

        $modelAssignment = ModelCharacteristicFactory::new()->create([
            'product_id' => $product->id,
            'characteristic_id' => $characteristic->id,
            'sync_status' => 'pending',
        ]);
        $variantAssignment = VariantCharacteristicFactory::new()->create([
            'product_attribute_id' => $attribute->id,
            'characteristic_id' => $characteristic->id,
            'value_id' => $value->id,
            'sync_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson(
            route('settings.suppliers.content.characteristics.panel', $content->uid)
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('model_assignments.0.id', $modelAssignment->id)
            ->assertJsonPath('variant_assignments.0.id', $variantAssignment->id);
    }

    public function test_save_model_characteristics_creates_pending_assignment(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermissions();
        $product = ProductFactory::new()->create();
        $content = AiContentFactory::new()->create(['supplier_product_id' => $product->id]);
        $characteristic = ErpCharacteristic::first() ?? ErpCharacteristicFactory::new()->create();

        $response = $this->actingAs($user)->postJson(
            route('settings.suppliers.content.characteristics.model', $content->uid),
            ['characteristic_ids' => [$characteristic->id]]
        );

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('supplier_model_characteristics', [
            'product_id' => $product->id,
            'characteristic_id' => $characteristic->id,
            'sync_status' => 'pending',
        ]);
    }

    public function test_save_model_characteristics_fails_without_erp_id(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermissions();
        $content = AiContentFactory::new()->create(['supplier_product_id' => null]);
        $characteristic = ErpCharacteristic::first() ?? ErpCharacteristicFactory::new()->create();

        $response = $this->actingAs($user)->postJson(
            route('settings.suppliers.content.characteristics.model', $content->uid),
            ['characteristic_ids' => [$characteristic->id]]
        );

        $response->assertStatus(422);
    }

    public function test_save_variant_characteristic_creates_pending_assignment_with_erp_article_id(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermissions();
        $product = ProductFactory::new()->create();
        $attribute = ProductAttributeFactory::new()->create(['product_id' => $product->id]);
        $content = AiContentFactory::new()->create(['supplier_product_id' => $product->id]);
        $characteristic = ErpCharacteristic::first() ?? ErpCharacteristicFactory::new()->create();
        $value = ErpCharacteristicValueFactory::new()->create(['characteristic_id' => $characteristic->id]);

        $response = $this->actingAs($user)->postJson(
            route('settings.suppliers.content.characteristics.variant', $content->uid),
            [
                'product_attribute_id' => $attribute->id,
                'characteristic_id' => $characteristic->id,
                'value_id' => $value->id,
            ]
        );

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('supplier_variant_characteristics', [
            'product_attribute_id' => $attribute->id,
            'characteristic_id' => $characteristic->id,
            'value_id' => $value->id,
            'sync_status' => 'pending',
            'erp_article_id' => $attribute->erp_id,
        ]);
    }

    public function test_save_variant_characteristic_updates_existing_and_resets_to_pending(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermissions();
        $product = ProductFactory::new()->create();
        $attribute = ProductAttributeFactory::new()->create(['product_id' => $product->id]);
        $content = AiContentFactory::new()->create(['supplier_product_id' => $product->id]);
        $characteristic = ErpCharacteristic::first() ?? ErpCharacteristicFactory::new()->create();
        $oldValue = ErpCharacteristicValueFactory::new()->create(['characteristic_id' => $characteristic->id]);
        $newValue = ErpCharacteristicValueFactory::new()->create(['characteristic_id' => $characteristic->id]);

        $existing = VariantCharacteristicFactory::new()->create([
            'product_attribute_id' => $attribute->id,
            'characteristic_id' => $characteristic->id,
            'value_id' => $oldValue->id,
            'sync_status' => 'synced',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('settings.suppliers.content.characteristics.variant', $content->uid),
            [
                'product_attribute_id' => $attribute->id,
                'characteristic_id' => $characteristic->id,
                'value_id' => $newValue->id,
            ]
        );

        $response->assertOk()->assertJsonPath('success', true);

        $existing->refresh();
        $this->assertSame($newValue->id, $existing->value_id);
        $this->assertSame('pending', $existing->sync_status);
        $this->assertSame(1, VariantCharacteristic::where('product_attribute_id', $attribute->id)
            ->where('characteristic_id', $characteristic->id)
            ->count());
    }

    public function test_characteristic_values_endpoint_returns_only_values_of_that_characteristic(): void
    {
        $this->skipIfTablesMissing();

        $user = $this->userWithPermissions();
        $characteristicA = ErpCharacteristicFactory::new()->create();
        $characteristicB = ErpCharacteristicFactory::new()->create();
        $valueA = ErpCharacteristicValueFactory::new()->create(['characteristic_id' => $characteristicA->id]);
        $valueB = ErpCharacteristicValueFactory::new()->create(['characteristic_id' => $characteristicB->id]);

        $response = $this->actingAs($user)->getJson(
            route('settings.suppliers.content.characteristics.values', $characteristicA->id)
        );

        $response->assertOk()->assertJsonPath('success', true);

        $ids = collect($response->json('values'))->pluck('id');
        $this->assertTrue($ids->contains($valueA->id));
        $this->assertFalse($ids->contains($valueB->id));
    }

    private function skipIfTablesMissing(): void
    {
        foreach ([
            'supplier_products',
            'supplier_product_attributes',
            'supplier_ai_contents',
            'supplier_erp_characteristics',
            'supplier_erp_characteristic_values',
            'supplier_model_characteristics',
            'supplier_variant_characteristics',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table {$table} is not present in the test database.");
            }
        }
    }
}
