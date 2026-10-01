<?php

namespace Modules\HelpdeskChatFlow\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowTemplateLibrary;

/**
 * Procedimientos listos para usar (estado de pedido, devoluciones, aviso de
 * stock). Se crean ACTIVOS: al ser de tipo `procedure` nunca se disparan solos,
 * solo corren cuando otro flujo (o un caso del asistente IA) los llama.
 *
 * Idempotente: si ya existe un procedimiento con ese nombre no se toca, de modo
 * que volver a ejecutarlo no pisa lo editado desde el panel.
 *
 * Las acciones que usan (consultar_pedido, pedido_reembolsable, cambios_rever,
 * aviso_stock) las siembra AiActionCatalogSeeder INACTIVAS: hay que activarlas
 * en el catálogo para que los procedimientos funcionen.
 */
class ProcedureFlowsSeeder extends Seeder
{
    public function run(ChatFlowTemplateLibrary $templates): void
    {
        $createdBy = User::query()->orderBy('id')->value('id');

        foreach ($templates->procedures() as $meta) {
            $built = $templates->buildProcedure($meta['key']);

            $flow = ChatFlow::query()->firstOrCreate(
                ['name' => $built['name'], 'trigger_type' => ChatFlow::TRIGGER_PROCEDURE],
                [
                    'uid' => (string) Str::uuid(),
                    'description' => $built['description'],
                    'nodes' => $built['nodes'],
                    'published_nodes' => $built['nodes'],
                    'status' => 'active',
                    'created_by' => $createdBy,
                    'published_at' => now(),
                ],
            );

            $this->command?->info("{$flow->name}: id {$flow->id}");
        }
    }
}
