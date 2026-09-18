<?php

namespace Modules\Supplier\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Models\Supplier\Supplier;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    public function test_scope_active_filters_correctly(): void
    {
        $query = Supplier::query()->active();
        $sql = $query->toSql();

        $this->assertStringContainsString('`available` = ?', $sql);
        $this->assertSame([true], $query->getBindings());
    }

    public function test_scope_by_erp_id_filters_correctly(): void
    {
        $query = Supplier::query()->byErpId(123);
        $sql = $query->toSql();

        $this->assertStringContainsString('`erp_id` = ?', $sql);
        $this->assertSame([123], $query->getBindings());
    }

    public function test_scope_search_filters_label_and_code(): void
    {
        $query = Supplier::query()->search('test');
        $sql = $query->toSql();

        $this->assertStringContainsString('`label` LIKE ?', $sql);
        $this->assertStringContainsString('`code` LIKE ?', $sql);
    }

    public function test_scope_by_uid_filters_correctly(): void
    {
        $query = Supplier::query()->byUid('01HX123ABC');
        $sql = $query->toSql();

        $this->assertStringContainsString('`uid` = ?', $sql);
        $this->assertSame(['01HX123ABC'], $query->getBindings());
    }

    public function test_fillable_attributes(): void
    {
        $supplier = new Supplier;
        $expected = [
            'erp_id',
            'code',
            'label',
            'description',
            'cif',
            'email',
            'available',
            'priority',
            'metadata',
            'erp_created_at',
            'erp_updated_at',
            'last_sync_at',
        ];

        $this->assertSame($expected, $supplier->getFillable());
    }

    public function test_casts_are_correct(): void
    {
        $supplier = new Supplier([
            'erp_id' => '123',
            'available' => '1',
            'priority' => '5',
        ]);

        $this->assertSame(123, $supplier->erp_id);
        $this->assertTrue($supplier->available);
        $this->assertSame(5, $supplier->priority);
    }

    public function test_metadata_cast_works_after_saving(): void
    {
        $supplier = new Supplier;
        $supplier->metadata = ['key' => 'value'];

        $this->assertSame(['key' => 'value'], $supplier->metadata);
    }

    public function test_relationship_methods_exist(): void
    {
        $supplier = new Supplier;

        $this->assertInstanceOf(BelongsToMany::class, $supplier->categories());
        $this->assertInstanceOf(HasMany::class, $supplier->products());
        $this->assertInstanceOf(HasMany::class, $supplier->prompts());
        $this->assertInstanceOf(HasMany::class, $supplier->sources());
    }
}
