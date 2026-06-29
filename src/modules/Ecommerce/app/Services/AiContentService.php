<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\Product;

class AiContentService
{
    public function generateDescription(Product $product): ?string
    {
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (! $apiKey) {
            return null;
        }

        $product->loadMissing(['brand', 'categories']);

        $prompt = "Eres un copywriter experto en ecommerce. Escribe una descripción de producto persuasiva y concisa (200-300 palabras) en español para:\n\n";
        $prompt .= "Producto: {$product->name}\n";
        if ($product->sku) {
            $prompt .= "SKU: {$product->sku}\n";
        }
        if ($product->brand) {
            $prompt .= "Marca: {$product->brand->name}\n";
        }
        if ($product->categories && $product->categories->count() > 0) {
            $prompt .= 'Categorías: '.$product->categories->pluck('name')->implode(', ')."\n";
        }
        $prompt .= "\nLa descripción debe:\n";
        $prompt .= "- Destacar beneficios, no solo features\n";
        $prompt .= "- Usar un tono cercano pero profesional\n";
        $prompt .= "- Incluir un llamado a la acción al final\n";
        $prompt .= "- Estar formateada en HTML simple (h3, p, ul/li)\n";
        $prompt .= "- NO mencionar precio (eso lo maneja el sistema)\n";

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 600,
                    'temperature' => 0.7,
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Throwable $e) {
            Log::error('AI generation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
