<?php

namespace Modules\HelpdeskChatFlow\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
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

            $this->linkToPromptCase($meta['key'], $flow);
        }
    }

    /**
     * Procedimiento → caso de la librería de prompts (HelpdeskAiPrompts), solo
     * si el caso aún no tiene uno: nunca pisa lo configurado en el panel.
     */
    private function linkToPromptCase(string $procedureKey, ChatFlow $flow): void
    {
        $caseKey = self::PROMPT_CASES[$procedureKey] ?? null;
        $caseClass = AiPromptCase::class;

        if ($caseKey === null || ! class_exists($caseClass)) {
            return;
        }

        $linked = $caseClass::query()
            ->where('key', $caseKey)
            ->whereNull('procedure_flow_id')
            ->update(['procedure_flow_id' => $flow->id]);

        if ($linked > 0) {
            $this->command?->info("  → asignado al caso de prompt {$caseKey}");
        }
    }

    /** Procedimiento → caso de prompt que lo usa. aviso_stock se llama desde otros flujos. */
    private const PROMPT_CASES = [
        'estado_pedido' => 'estado_pedido',
        'devoluciones' => 'devoluciones_cambios',
    ];
}
