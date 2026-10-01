<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Mockery;
use Mockery\MockInterface;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionRegistry;
use Modules\HelpdeskAiPrompts\Services\Actions\HostResolver;
use Modules\HelpdeskAiPrompts\Tests\Feature\HelpdeskAiPromptsTestCase;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

abstract class AiActionsTestCase extends HelpdeskAiPromptsTestCase
{
    protected FakeHostResolver $dns;

    protected function setUp(): void
    {
        parent::setUp();

        // Dentro de la transacción del test: no toca el catálogo real sembrado.
        AiAction::query()->delete();
        AiActionRun::query()->delete();
        ActionRegistry::forget();

        config()->set('ai-actions.http_allowed_hosts', ['api.example.com']);

        $this->dns = new FakeHostResolver;
        $this->app->instance(HostResolver::class, $this->dns);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function bridge(array $overrides = []): AiAction
    {
        return AiAction::query()->create($overrides + [
            'key' => 'documentos_pedido',
            'name' => 'Documentos',
            'description' => 'Documentos de un pedido',
            'type' => 'bridge',
            'is_active' => true,
            'parameters' => [],
            'config' => ['action' => 'order.documents', 'payload' => ['order_id' => '{{order.id}}']],
            'response' => ['fields' => ['invoices.*.number']],
            'rules' => ['ownership' => 'order_email_pair'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function http(array $overrides = []): AiAction
    {
        return AiAction::query()->create($overrides + [
            'key' => 'consulta_externa',
            'name' => 'Consulta externa',
            'description' => 'Consulta un servicio externo',
            'type' => 'http',
            'is_active' => true,
            'parameters' => [['name' => 'q', 'type' => 'string', 'description' => 'Búsqueda', 'required' => true]],
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/search?q={{args.q}}'],
            'response' => ['fields' => ['items.*.name']],
            'rules' => ['ownership' => 'none'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function ctx(array $overrides = []): array
    {
        return $overrides + [
            'verified' => false,
            'customer_email' => null,
            'customer_ps_id' => null,
            'customer_erp_id' => null,
            'conversation_id' => random_int(1_000_000, 9_999_999),
            'trace_id' => 'trace-'.uniqid(),
            'channel' => 'widget',
            'locale' => 'es',
        ];
    }

    protected function verifiedCtx(array $overrides = []): array
    {
        return $this->ctx($overrides + [
            'verified' => true,
            'customer_email' => 'ana@example.com',
            'customer_ps_id' => 77,
        ]);
    }

    protected function ps(): MockInterface
    {
        $ps = Mockery::mock(PrestashopContextService::class);
        $ps->shouldReceive('ownershipLookup')->andReturnUsing(function (?string $email, ?int $externalId): ?array {
            $lookup = array_filter(['email' => $email, 'external_id' => $externalId], fn ($v) => $v !== null);

            return $lookup === [] ? null : $lookup;
        })->byDefault();
        $this->app->instance(PrestashopContextService::class, $ps);

        return $ps;
    }
}

class FakeHostResolver extends HostResolver
{
    /** @var array<string, array<int, string>> */
    public array $map = [];

    public function resolve(string $host): array
    {
        return $this->map[$host] ?? ['93.184.216.34'];
    }
}
