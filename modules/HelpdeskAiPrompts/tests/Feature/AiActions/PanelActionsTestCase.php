<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use App\Models\User;
use Modules\HelpdeskAiPrompts\Database\Seeders\HelpdeskAiPromptsPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

abstract class PanelActionsTestCase extends AiActionsTestCase
{
    protected User $viewer;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskAiPromptsPermissionsSeeder::class);

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo('helpdesk.ai-prompts.view');

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['helpdesk.ai-prompts.view', 'helpdesk.ai-prompts.manage']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function bridgePayload(array $overrides = []): array
    {
        return $overrides + [
            'type' => 'bridge',
            'key' => 'documentos_pedido',
            'name' => 'Documentos',
            'description' => 'Documentos de un pedido',
            'is_active' => 1,
            'config' => ['action' => 'order.documents', 'payload' => '{"order_id": "{{order.id}}"}'],
            'parameters' => [],
            'response' => ['fields' => ['invoices.*.number'], 'max_chars' => 1200, 'empty_message' => 'Nada'],
            'rules' => ['ownership' => 'order_email_pair', 'max_per_conversation' => 5, 'timeout' => 8],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function httpPayload(array $overrides = []): array
    {
        return $overrides + [
            'type' => 'http',
            'key' => 'consulta_externa',
            'name' => 'Consulta externa',
            'description' => 'Consulta un servicio externo',
            'is_active' => 1,
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/search?q={{args.q}}', 'headers' => '{"X-Source": "helpdesk"}'],
            'auth' => ['type' => 'bearer', 'value' => 'sekret-123'],
            'parameters' => [['name' => 'q', 'type' => 'string', 'description' => 'Búsqueda', 'required' => 1]],
            'response' => ['fields' => ['items.*.name']],
            'rules' => ['ownership' => 'none', 'max_per_conversation' => 5, 'timeout' => 8],
        ];
    }
}
