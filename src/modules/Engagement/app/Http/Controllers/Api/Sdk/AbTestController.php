<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Engagement\Models\AbTest;
use Modules\Engagement\Models\AbTestVariant;

class AbTestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $inbox = $request->attributes->get('website_inbox');
        $sessionToken = $request->header('X-Session-Token') ?? '';

        $tests = AbTest::query()
            ->forInbox($inbox->id)
            ->active()
            ->with('variants')
            ->get();

        $assigned = [];

        foreach ($tests as $test) {
            $variant = $this->assignVariant($test, $sessionToken);
            if ($variant) {
                // Increment impression asynchronously via queue or direct
                AbTestVariant::query()->where('id', $variant->id)->increment('impressions');
                $assigned[] = [
                    'testId' => $test->id,
                    'testName' => $test->name,
                    'variantId' => $variant->id,
                    'variantName' => $variant->name,
                    'config' => $variant->config,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $assigned,
        ]);
    }

    private function assignVariant(AbTest $test, string $sessionToken): ?AbTestVariant
    {
        $variants = $test->variants;
        if ($variants->isEmpty()) {
            return null;
        }

        $totalWeight = (int) $variants->sum('weight');
        if ($totalWeight <= 0) {
            return $variants->first();
        }

        $hash = crc32($test->id.':'.$sessionToken);
        $bucket = $hash % $totalWeight;

        $accumulated = 0;
        foreach ($variants as $variant) {
            $accumulated += (int) $variant->weight;
            if ($bucket < $accumulated) {
                return $variant;
            }
        }

        return $variants->last();
    }
}
