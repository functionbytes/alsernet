<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Features\EcommerceFeatures;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;
use Modules\Ecommerce\Models\Product;

class ChatbotController extends Controller
{
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $message = strtolower($request->input('message'));

        return response()->json(['response' => $this->getResponse($message)]);
    }

    private function getResponse(string $message): array
    {
        if ($this->matches($message, ['producto', 'busco', 'tienes'])) {
            return $this->searchProducts($message);
        }

        if ($this->matches($message, ['envío', 'envio', 'shipping', 'entrega'])) {
            return ['text' => 'Hacemos envíos a todo el país. Tiempos: 2-8 días hábiles según destino. Envío gratis en compras superiores a $200. Más detalles en nuestra <a href="'.route('shop.legal.show', 'shipping-policy').'">política de envíos</a>.'];
        }

        if ($this->matches($message, ['pago', 'tarjeta', 'pse', 'nequi'])) {
            return ['text' => 'Aceptamos: tarjeta de crédito/débito (Wompi), PSE, Nequi, transferencia bancaria y pago contra entrega (COD).'];
        }

        if ($this->matches($message, ['devol', 'cambio', 'reembolso'])) {
            return ['text' => 'Tienes 30 días desde la recepción para devolver tu producto. Más información en nuestra <a href="'.route('shop.legal.show', 'returns-policy').'">política de devoluciones</a>.'];
        }

        if ($this->matches($message, ['cuenta', 'registr', 'login'])) {
            return ['text' => 'Puedes <a href="'.route('ecommerce.register').'">crear una cuenta</a> o <a href="'.route('ecommerce.login').'">iniciar sesión</a> en nuestro portal de clientes.'];
        }

        if ($this->matches($message, ['contact', 'teléfono', 'telefono', 'email'])) {
            return ['text' => 'Puedes contactarnos por teléfono o email. Más detalles en <a href="'.route('shop.legal.show', 'contact').'">nuestra página de contacto</a>.'];
        }

        if ($this->matches($message, ['hola', 'buenas', 'hey', 'hi'])) {
            return ['text' => '¡Hola! ¿En qué puedo ayudarte? Puedo darte información sobre productos, envíos, pagos, devoluciones o tu cuenta.'];
        }

        if ($this->matches($message, ['gracias', 'thank'])) {
            return ['text' => '¡De nada! Si necesitas algo más, estoy aquí para ayudarte.'];
        }

        if (Feature::active(EcommerceFeatures::CHATBOT_LLM)) {
            $llmResponse = $this->askLlm($message);
            if ($llmResponse) {
                return ['text' => $llmResponse];
            }
        }

        return [
            'text' => 'No estoy seguro de cómo ayudarte con eso. Puedes preguntarme sobre: productos, envíos, métodos de pago, devoluciones, o tu cuenta. También puedes <a href="'.route('shop.legal.show', 'contact').'">contactarnos directamente</a>.',
        ];
    }

    private function askLlm(string $message): ?string
    {
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (! $apiKey) {
            return null;
        }

        $products = Product::query()
            ->where('status', 'published')
            ->where('is_variation', false)
            ->limit(20)
            ->get(['name', 'slug', 'price', 'sku']);

        $catalog = $products->map(fn ($p) => "- {$p->name} (\${$p->price})")->implode("\n");

        $storeName = config('app.name');
        $systemPrompt = "Eres un asistente virtual de la tienda en línea {$storeName}. Responde con tono amigable y profesional. Solo responde sobre la tienda, productos, envíos, pagos, devoluciones. Si no sabes algo, sugerí contactar con soporte.\n\nProductos destacados disponibles:\n{$catalog}\n\nResponde en español, en máximo 80 palabras.";

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'max_tokens' => 200,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (\Throwable $e) {
            Log::warning('Chatbot LLM failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function matches(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function searchProducts(string $message): array
    {
        $words = preg_split('/\s+/', $message);
        $longWords = array_filter($words, fn ($w) => mb_strlen($w) > 4);

        if (empty($longWords)) {
            return ['text' => 'Puedes buscar productos en nuestra <a href="'.route('shop.index').'">tienda</a>.'];
        }

        $term = end($longWords);
        $products = Product::query()
            ->where('status', 'published')
            ->where('name', 'like', "%{$term}%")
            ->limit(3)
            ->get(['name', 'slug', 'price']);

        if ($products->isEmpty()) {
            return ['text' => "No encontré productos con \"{$term}\". Prueba con otro término en nuestra <a href=\"".route('shop.index').'">tienda</a>.'];
        }

        return [
            'text' => "Encontré estos productos relacionados con \"{$term}\":",
            'products' => $products->map(fn ($p) => [
                'name' => $p->name,
                'price' => '$'.number_format($p->price, 2),
                'url' => route('shop.product', $p->slug),
            ]),
        ];
    }
}
