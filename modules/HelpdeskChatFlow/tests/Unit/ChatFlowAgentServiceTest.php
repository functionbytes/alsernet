<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;
use Modules\HelpdeskChatFlow\Services\ChatFlowOrderLookup;
use Modules\HelpdeskChatFlow\Tests\TestCase;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;

class ChatFlowAgentServiceTest extends TestCase
{
    public function test_escalates_without_api_key(): void
    {
        config()->set('services.openai.key', '');

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('hola', [], ['fallback_message' => 'Te paso con un agente.']);

        $this->assertSame('escalate', $result['action']);
        $this->assertSame('Te paso con un agente.', $result['text']);
    }

    public function test_answers_directly_when_llm_returns_content(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Claro, te ayudo con eso.', 'tool_calls' => []]]],
            ], 200),
        ]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('una pregunta', [], []);

        $this->assertSame('respond', $result['action']);
        $this->assertSame('Claro, te ayudo con eso.', $result['text']);
    }

    public function test_runs_order_lookup_tool_then_answers(): void
    {
        config()->set('services.openai.key', 'sk-test');

        // 1st call: the model asks to call lookup_order. 2nd call: it answers.
        Http::fakeSequence('api.openai.com/*')
            ->push(['choices' => [['message' => [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => ['name' => 'lookup_order', 'arguments' => '{"order_id":"100245"}'],
                ]],
            ]]]], 200)
            ->push(['choices' => [['message' => ['content' => 'Tu pedido #100245 está enviado.', 'tool_calls' => []]]]], 200);

        $erp = new class
        {
            public function getOrderDetail(int $customerId, int $orderId): ?array
            {
                return ['id' => $orderId, 'status' => 'Enviado', 'total' => '49,90 €'];
            }
        };

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup($erp, null), null);
        $result = $agent->run('¿dónde está mi pedido 100245?', ['customer_erp_id' => 10], []);

        $this->assertSame('respond', $result['action']);
        $this->assertStringContainsString('enviado', mb_strtolower($result['text']));
        $this->assertContains('lookup_order', $result['used_tools']);
    }

    public function test_agent_can_escalate_via_tool(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_x',
                    'type' => 'function',
                    'function' => ['name' => 'escalate_to_agent', 'arguments' => '{"message":"Te paso con un agente."}'],
                ]],
            ]]]], 200),
        ]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('quiero una persona', [], []);

        $this->assertSame('escalate', $result['action']);
        $this->assertContains('escalate_to_agent', $result['used_tools']);
    }

    private function fakeCatalog(): object
    {
        return new class
        {
            public array $queries = [];

            public function search(string $query, int $limit = 6): array
            {
                $this->queries[] = $query;

                return [
                    CatalogProduct::fromArray([
                        'id' => '43141', 'title' => 'Estuche de limpieza MAXI', 'price' => 49.99, 'currency' => 'EUR',
                    ]),
                ];
            }

            public function find(string $id): ?object
            {
                return $id === '43141'
                    ? CatalogProduct::fromArray([
                        'id' => '43141', 'title' => 'Estuche de limpieza MAXI', 'description' => '60 piezas', 'price' => 49.99,
                    ])
                    : null;
            }
        };
    }

    public function test_product_search_tool_returns_products_to_show(): void
    {
        config()->set('services.openai.key', 'sk-test');

        Http::fakeSequence('api.openai.com/*')
            ->push(['choices' => [['message' => [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_p',
                    'type' => 'function',
                    'function' => ['name' => 'product_search', 'arguments' => '{"query":"estuche limpieza"}'],
                ]],
            ]]]], 200)
            ->push(['choices' => [['message' => ['content' => 'Te muestro un estuche que encaja.', 'tool_calls' => []]]]], 200);

        $catalog = $this->fakeCatalog();
        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('busco un estuche de limpieza', [], [], 'es', $catalog);

        $this->assertSame('respond', $result['action']);
        $this->assertContains('product_search', $result['used_tools']);
        $this->assertSame(['estuche limpieza'], $catalog->queries);
        $this->assertCount(1, $result['products']);
        $this->assertSame('43141', $result['products'][0]->id);
    }

    public function test_product_tools_are_not_offered_without_catalog(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Hola', 'tool_calls' => []]]],
            ], 200),
        ]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('hola', [], []);

        Http::assertSent(function ($request) {
            $names = array_map(fn ($t) => $t['function']['name'] ?? '', $request->data()['tools'] ?? []);

            return ! in_array('product_search', $names, true) && ! in_array('product_detail', $names, true);
        });
        $this->assertSame([], $result['products']);
    }

    public function test_product_tools_can_be_disabled_per_node(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Hola', 'tool_calls' => []]]],
            ], 200),
        ]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $agent->run('hola', [], ['tool_products' => false], 'es', $this->fakeCatalog());

        Http::assertSent(function ($request) {
            $names = array_map(fn ($t) => $t['function']['name'] ?? '', $request->data()['tools'] ?? []);

            return ! in_array('product_search', $names, true);
        });
    }

    private function fakeCart(): object
    {
        return new class
        {
            public array $added = [];

            public function show(): ?array
            {
                return ['products_count' => 1, 'total' => 57.98, 'currency' => 'EUR', 'lines' => [['id_product' => 43141, 'name' => 'Estuche', 'qty' => 1, 'total' => 49.99]]];
            }

            public function add(int $productId, int $attributeId, int $quantity): array
            {
                $this->added[] = [$productId, $attributeId, $quantity];

                return ['ok' => true];
            }
        };
    }

    private function toolCall(string $name, array $args): array
    {
        return ['choices' => [['message' => [
            'content' => null,
            'tool_calls' => [['id' => 'call_'.$name, 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($args)]]],
        ]]]];
    }

    public function test_add_to_cart_requires_customer_confirmation(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fakeSequence('api.openai.com/*')
            ->push($this->toolCall('add_to_cart', ['product_id' => '43141', 'customer_confirmed' => false]), 200)
            ->push(['choices' => [['message' => ['content' => '¿Quieres que lo añada?', 'tool_calls' => []]]]], 200);

        $cart = $this->fakeCart();
        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $agent->run('me interesa el estuche', [], [], 'es', $this->fakeCatalog(), $cart);

        $this->assertSame([], $cart->added);
    }

    public function test_add_to_cart_adds_when_confirmed(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fakeSequence('api.openai.com/*')
            ->push($this->toolCall('add_to_cart', ['product_id' => '43141', 'quantity' => 2, 'customer_confirmed' => true]), 200)
            ->push(['choices' => [['message' => ['content' => 'Listo, añadido.', 'tool_calls' => []]]]], 200);

        $cart = $this->fakeCart();
        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('sí, añade dos', [], [], 'es', $this->fakeCatalog(), $cart);

        $this->assertSame([[43141, 0, 2]], $cart->added);
        $this->assertContains('add_to_cart', $result['used_tools']);
    }

    public function test_cart_tools_need_catalog(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'Hola', 'tool_calls' => []]]]], 200)]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $agent->run('hola', [], [], 'es', null, $this->fakeCart());

        Http::assertSent(function ($request) {
            $names = array_map(fn ($t) => $t['function']['name'] ?? '', $request->data()['tools'] ?? []);

            return ! in_array('add_to_cart', $names, true) && ! in_array('show_cart', $names, true);
        });
    }

    public function test_history_turns_are_sent_before_the_question(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'Claro.', 'tool_calls' => []]]]], 200)]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $agent->run('¿y el envío?', [], [], 'es', null, null, [
            ['role' => 'user', 'content' => '¿Tenéis la talla M?'],
            ['role' => 'assistant', 'content' => 'Sí, disponible.'],
        ]);

        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $roles = array_column($messages, 'role');

            // system, history(user, assistant), user(question) — in that order.
            return $roles === ['system', 'user', 'assistant', 'user']
                && $messages[1]['content'] === '¿Tenéis la talla M?'
                && $messages[2]['content'] === 'Sí, disponible.'
                && str_contains($messages[3]['content'], '¿y el envío?');
        });
    }

    public function test_visitor_context_is_fenced_and_sent_to_the_model(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'Claro.', 'tool_calls' => []]]]], 200)]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $agent->run('hola', [], [], 'es', null, null, [], [
            'current_product' => ['id' => '43141', 'title' => 'Estuche de limpieza MAXI', 'price' => 49.99, 'currency' => 'EUR'],
            'cart' => null,
            'viewed_products' => [],
        ]);

        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $visitorMessage = $messages[1] ?? null;

            return $visitorMessage !== null
                && $visitorMessage['role'] === 'system'
                && str_contains($visitorMessage['content'], 'CONTEXTO_VISITANTE')
                && str_contains($visitorMessage['content'], '43141')
                && str_contains($visitorMessage['content'], 'Estuche de limpieza MAXI');
        });
    }

    public function test_product_detail_falls_back_to_current_product_id_from_context(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fakeSequence('api.openai.com/*')
            ->push($this->toolCall('product_detail', []), 200)
            ->push(['choices' => [['message' => ['content' => 'Es un estuche de 60 piezas.', 'tool_calls' => []]]]], 200);

        $catalog = $this->fakeCatalog();
        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('¿qué trae este producto?', ['current_product_id' => '43141'], [], 'es', $catalog);

        $this->assertSame('respond', $result['action']);
        $this->assertContains('product_detail', $result['used_tools']);
    }

    public function test_empty_answer_customer_escalates_instead_of_an_empty_bubble(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_empty',
                    'type' => 'function',
                    'function' => ['name' => 'answer_customer', 'arguments' => '{"text":"   "}'],
                ]],
            ]]]], 200),
        ]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('hola', [], ['fallback_message' => 'Te paso con un agente.']);

        $this->assertSame('escalate', $result['action']);
        $this->assertSame('Te paso con un agente.', $result['text']);
    }

    public function test_blank_final_content_without_tool_calls_escalates(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '   ', 'tool_calls' => []]]]], 200),
        ]);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null);
        $result = $agent->run('hola', [], ['fallback_message' => 'Te paso con un agente.']);

        $this->assertSame('escalate', $result['action']);
        $this->assertSame('Te paso con un agente.', $result['text']);
    }

    // ─── Pedidos: número + email / cliente verificado ──────────────────────────

    private function fakePs(): object
    {
        return new class
        {
            public array $calls = [];

            public function getOrderDetail(int $orderId, ?string $email, mixed $psId = null): ?array
            {
                $this->calls[] = [$orderId, $email];

                return $orderId === 5001 && $email === 'cliente@example.com'
                    ? ['id' => 5001, 'reference' => 'XKBKNABJK', 'state_name' => 'Enviado', 'totals' => ['total' => 146.99], 'currency' => 'EUR',
                        'created_at' => '2026-09-20 10:00:00',
                        'tracking' => [['tracking_number' => 'PK123', 'carrier_name' => 'GLS', 'tracking_url' => 'https://gls.example/PK123', 'date' => '2026-09-21']],
                        'lines' => [['name' => 'Botas Chiruca Malviz', 'quantity' => 1]],
                        'addresses' => ['delivery' => ['address1' => 'Calle Secreta 1']]]
                    : null;
            }

            public function getCustomerOrders(?string $email, mixed $psId, int $limit, int $page): array
            {
                return ['orders' => [['id' => 5001, 'reference' => 'XKBKNABJK', 'state_name' => 'Enviado', 'created_at' => '2026-09-20', 'total_paid' => 146.99]]];
            }
        };
    }

    public function test_unverified_order_lookup_needs_email_and_matches_it(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fakeSequence('api.openai.com/*')
            ->push($this->toolCall('lookup_order', ['order_id' => '5001', 'email' => 'Cliente@Example.com']), 200)
            ->push(['choices' => [['message' => ['content' => 'Tu pedido está enviado con GLS.', 'tool_calls' => []]]]], 200);

        $ps = $this->fakePs();
        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, $ps), null);
        $result = $agent->run('¿dónde está mi pedido 5001? cliente@example.com', ['_trace_id' => 't-order-1'], []);

        $this->assertSame('respond', $result['action']);
        $this->assertSame([[5001, 'cliente@example.com']], $ps->calls);

        // Lo que vio el modelo: estado y seguimiento, nunca la dirección.
        Http::assertSent(function ($request) {
            $tool = collect($request->data()['messages'] ?? [])->firstWhere('role', 'tool');
            if (! $tool) {
                return false;
            }

            return str_contains($tool['content'], 'Enviado')
                && str_contains($tool['content'], 'https://gls.example/PK123')
                && ! str_contains($tool['content'], 'Calle Secreta');
        });
    }

    public function test_unverified_order_lookup_is_rate_limited(): void
    {
        config()->set('services.openai.key', 'sk-test');
        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, $this->fakePs()), null);

        $sequence = Http::fakeSequence('api.openai.com/*');
        for ($i = 0; $i < 6; $i++) {
            $sequence->push($this->toolCall('lookup_order', ['order_id' => (string) (7000 + $i), 'email' => 'otro@example.com']), 200)
                ->push(['choices' => [['message' => ['content' => 'ok', 'tool_calls' => []]]]], 200);
        }
        for ($i = 0; $i < 6; $i++) {
            $agent->run('pedido', ['_trace_id' => 't-order-limit'], []);
        }

        Http::assertSent(function ($request) {
            $tool = collect($request->data()['messages'] ?? [])->firstWhere('role', 'tool');

            return $tool && str_contains($tool['content'], 'Demasiados intentos');
        });
    }

    public function test_verified_customer_gets_list_my_orders_without_email(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fakeSequence('api.openai.com/*')
            ->push($this->toolCall('list_my_orders', []), 200)
            ->push(['choices' => [['message' => ['content' => 'Tu último pedido está enviado.', 'tool_calls' => []]]]], 200);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, $this->fakePs()), null);
        $result = $agent->run('mis pedidos', ['identity_verified' => true, 'customer_email' => 'cliente@example.com'], []);

        $this->assertContains('list_my_orders', $result['used_tools']);
        Http::assertSent(function ($request) {
            $names = array_map(fn ($t) => $t['function']['name'] ?? '', $request->data()['tools'] ?? []);
            $lookup = collect($request->data()['tools'] ?? [])->firstWhere('function.name', 'lookup_order');

            // Verificado: no se le pide el email al cliente.
            return in_array('list_my_orders', $names, true)
                && ! array_key_exists('email', $lookup['function']['parameters']['properties'] ?? []);
        });
    }

    // ─── Tallas / variantes ─────────────────────────────────────────────────────

    private function fakeInsights(): object
    {
        return new class
        {
            private array $options = [
                ['id_product_attribute' => 911, 'label' => 'Talla: 43', 'available' => false, 'stock_level' => 'agotado', 'delivery_text' => null],
                ['id_product_attribute' => 912, 'label' => 'Talla: 44', 'available' => true, 'stock_level' => 'disponible', 'delivery_text' => 'Envío en 24/48 h'],
            ];

            public function variants(int $productId, ?string $lang = null): ?array
            {
                return $productId === 60766 ? ['product_id' => 60766, 'title' => 'Botas Chiruca Malviz', 'options' => $this->options, 'delivery_text' => 'Envío en 24/48 h', 'in_store_stock' => []] : null;
            }

            public function optionFor(int $productId, string $wanted, ?string $lang = null): ?array
            {
                foreach ($this->options as $o) {
                    if (str_contains($o['label'], preg_replace('/\D/', '', $wanted))) {
                        return $o;
                    }
                }

                return null;
            }

            public function compare(array $ids, ?string $lang = null): ?array
            {
                return ['products' => $ids];
            }
        };
    }

    private function fakeBootCatalog(): object
    {
        return new class
        {
            public function search(string $query, int $limit = 6): array
            {
                return [];
            }

            public function find(string $id): ?object
            {
                return $id === '60766'
                    ? new CatalogProduct('60766', 'Botas Chiruca Malviz', price: 146.99, currency: 'EUR', idProductAttribute: 911, hasCombinations: true)
                    : null;
            }
        };
    }

    public function test_product_variants_answers_size_stock_for_current_product(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fakeSequence('api.openai.com/*')
            ->push($this->toolCall('product_variants', ['option' => '44']), 200)
            ->push(['choices' => [['message' => ['content' => 'Sí, la 44 está disponible.', 'tool_calls' => []]]]], 200);

        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null, null, null, $this->fakeInsights());
        $result = $agent->run('¿tenéis la 44?', ['current_product_id' => '60766'], [], 'es', $this->fakeBootCatalog());

        $this->assertContains('product_variants', $result['used_tools']);
        $this->assertSame('60766', $result['products'][0]->id);
        Http::assertSent(function ($request) {
            $tool = collect($request->data()['messages'] ?? [])->firstWhere('role', 'tool');

            return $tool && str_contains($tool['content'], '"asked_option"') && str_contains($tool['content'], 'Talla: 44');
        });
    }

    public function test_add_to_cart_uses_the_combination_of_the_chosen_size(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fakeSequence('api.openai.com/*')
            ->push($this->toolCall('add_to_cart', ['option' => 'talla 44', 'quantity' => 1, 'customer_confirmed' => true]), 200)
            ->push(['choices' => [['message' => ['content' => 'Añadida la talla 44.', 'tool_calls' => []]]]], 200);

        $cart = $this->fakeCart();
        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null, null, null, $this->fakeInsights());
        $agent->run('sí, añádela en la 44', ['current_product_id' => '60766'], [], 'es', $this->fakeBootCatalog(), $cart);

        $this->assertSame([[60766, 912, 1]], $cart->added);
    }

    public function test_add_to_cart_refuses_a_size_without_stock(): void
    {
        config()->set('services.openai.key', 'sk-test');
        Http::fakeSequence('api.openai.com/*')
            ->push($this->toolCall('add_to_cart', ['option' => '43', 'customer_confirmed' => true]), 200)
            ->push(['choices' => [['message' => ['content' => 'La 43 está agotada.', 'tool_calls' => []]]]], 200);

        $cart = $this->fakeCart();
        $agent = new ChatFlowAgentService(new ChatFlowOrderLookup(null, null), null, null, null, $this->fakeInsights());
        $agent->run('añade la 43', ['current_product_id' => '60766'], [], 'es', $this->fakeBootCatalog(), $cart);

        $this->assertSame([], $cart->added);
    }
}
