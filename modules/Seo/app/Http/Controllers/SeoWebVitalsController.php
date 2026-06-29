<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Seo\Http\Requests\StoreSeoWebVitalRequest;
use Modules\Seo\Models\SeoWebVital;

/**
 * Endpoint interno que recibe las métricas de Core Web Vitals
 * desde el beacon JS (@seoWebVitalsBeacon) y las almacena por URL.
 */
class SeoWebVitalsController extends Controller
{
    public function store(StoreSeoWebVitalRequest $request): JsonResponse
    {
        if (! config('seohelper.web_vitals.enabled', true)) {
            return response()->json(['success' => false, 'message' => 'disabled'], 404);
        }

        $data = $request->validated();

        $path = parse_url($data['url'], PHP_URL_PATH) ?: '/';

        SeoWebVital::create([
            'url' => $data['url'],
            'url_path' => $path,
            'metric' => strtoupper($data['metric']),
            'value' => (float) $data['value'],
            'rating' => $data['rating'] ?? SeoWebVital::rate(strtoupper($data['metric']), (float) $data['value']),
            'device' => $data['device'] ?? null,
            'connection' => $data['connection'] ?? null,
            'navigation_type' => $data['navigation_type'] ?? null,
            'captured_at' => now(),
        ]);

        return response()->json(['success' => true], 201);
    }
}
