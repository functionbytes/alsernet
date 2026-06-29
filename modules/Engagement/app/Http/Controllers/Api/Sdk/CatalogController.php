<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Engagement\Models\CatalogProduct;

class CatalogController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $inbox = $request->attributes->get('livechat_inbox');

        $validated = $request->validate([
            'products' => ['required', 'array', 'min:1', 'max:500'],
            'products.*.productId' => ['required', 'string', 'max:100'],
            'products.*.name' => ['required', 'string', 'max:255'],
            'products.*.price' => ['nullable', 'numeric', 'min:0'],
            'products.*.currency' => ['nullable', 'string', 'size:3'],
            'products.*.url' => ['nullable', 'url', 'max:500'],
            'products.*.imageUrl' => ['nullable', 'url', 'max:500'],
            'products.*.category' => ['nullable', 'string', 'max:150'],
            'products.*.description' => ['nullable', 'string', 'max:2000'],
            'products.*.metadata' => ['nullable', 'array'],
        ]);

        $now = now();
        $upserted = 0;

        DB::connection('helpdesk')->transaction(function () use ($validated, $inbox, $now, &$upserted) {
            foreach ($validated['products'] as $p) {
                CatalogProduct::query()->updateOrCreate(
                    [
                        'inbox_id' => $inbox->id,
                        'platform_product_id' => $p['productId'],
                    ],
                    [
                        'name' => $p['name'],
                        'description' => $p['description'] ?? null,
                        'price' => $p['price'] ?? 0,
                        'currency' => strtoupper($p['currency'] ?? 'EUR'),
                        'url' => $p['url'] ?? null,
                        'image_url' => $p['imageUrl'] ?? null,
                        'category' => $p['category'] ?? null,
                        'metadata' => $p['metadata'] ?? [],
                        'is_active' => true,
                        'synced_at' => $now,
                    ],
                );
                $upserted++;
            }
        });

        return response()->json([
            'success' => true,
            'data' => ['synced' => $upserted],
        ]);
    }
}
