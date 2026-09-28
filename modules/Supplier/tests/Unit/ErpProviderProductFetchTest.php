<?php

namespace Modules\Supplier\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Supplier\Services\Integrations\ErpProviderProductSyncService;
use Tests\TestCase;

class ErpProviderProductFetchTest extends TestCase
{
    private function fetch(int $id): array
    {
        $svc = app(ErpProviderProductSyncService::class);

        return (fn () => $this->fetchSupplierProducts($id))->call($svc);
    }

    public function test_merges_all_pages_and_unions_taxonomy_by_id(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
            $page = (int) ($q['offset'] ?? 0) === 0
                ? ['products' => [['id' => 1], ['id' => 2]], 'sports' => [['id' => 10]], 'categories' => [['id' => 20]], 'subfamilies' => [], 'pagination' => ['hasMore' => true]]
                : ['products' => [['id' => 3]], 'sports' => [['id' => 10], ['id' => 11]], 'categories' => [['id' => 21]], 'subfamilies' => [['id' => 30]], 'pagination' => ['hasMore' => false]];

            return Http::response(['success' => true, 'data' => $page]);
        });

        $data = $this->fetch(8007);

        $this->assertSame([1, 2, 3], array_column($data['products'], 'id'));
        $this->assertSame([10, 11], array_column($data['sports'], 'id'));
        $this->assertSame([20, 21], array_column($data['categories'], 'id'));
        $this->assertSame([30], array_column($data['subfamilies'], 'id'));
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'limit=500') && str_contains($r->url(), 'offset=500'));
    }

    public function test_a_non_paginating_erp_response_is_used_as_is(): void
    {
        Http::fake(['*' => Http::response(['success' => true, 'data' => [
            'products' => [['id' => 1], ['id' => 2]], 'sports' => [['id' => 10]], 'categories' => [], 'subfamilies' => [],
        ]])]);

        $data = $this->fetch(1007);

        $this->assertCount(2, $data['products']);
        Http::assertSentCount(1);
    }
}
