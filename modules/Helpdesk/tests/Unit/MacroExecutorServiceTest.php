<?php

namespace Modules\Helpdesk\Tests\Unit;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Macro;
use Modules\Helpdesk\Services\Macros\MacroExecutorService;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * El formulario real de macros (StoreMacroRequest/UpdateMacroRequest,
 * MacroFactory, HelpdeskDemoDataSeeder) guarda un `value` plano por accion,
 * no el `params` estructurado que espera cada AutomationAction. Antes de
 * MacroExecutorService::paramsFromValue() toda macro creada desde la UI se
 * "aplicaba" con params=[] y cada accion no hacia nada (confirmado en vivo
 * contra las 5 macros reales de /panel/settings/helpdesk/macros).
 *
 * Ademas, LiquidRenderer ya no se inyecta por constructor: el paquete del
 * que depende (keepsuit/liquid) no esta instalado, y su constructor
 * reventaba con 500 la aplicacion de CUALQUIER macro antes de ejecutar una
 * sola accion. test_resolving_the_service_does_not_require_liquid() fija
 * ese contrato.
 */
class MacroExecutorServiceTest extends HelpdeskTestCase
{
    private function macro(array $actions, bool $isShared = true): Macro
    {
        return Macro::factory()->create(['actions' => $actions, 'is_shared' => $isShared]);
    }

    public function test_resolving_the_service_does_not_require_liquid(): void
    {
        // Regresion: antes de quitar LiquidRenderer del constructor, esta
        // linea por si sola lanzaba "Class Keepsuit\Liquid\EnvironmentFactory
        // not found" — sin llegar siquiera a leer una accion del macro.
        $service = app(MacroExecutorService::class);

        $this->assertInstanceOf(MacroExecutorService::class, $service);
    }

    public function test_send_reply_uses_the_stored_value_as_body(): void
    {
        $conversation = Conversation::factory()->create();

        $macro = $this->macro([
            ['type' => 'send_reply', 'value' => 'Gracias por escribirnos.'],
        ]);

        $result = app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertSame(['send_reply'], $result['executed']);
        $this->assertSame([], $result['failed']);

        $message = $conversation->items()->latest('id')->first();
        $this->assertSame('Gracias por escribirnos.', $message->body);
        $this->assertFalse((bool) $message->is_internal);
    }

    public function test_add_note_uses_the_stored_value_as_note_text(): void
    {
        $conversation = Conversation::factory()->create();

        $macro = $this->macro([
            ['type' => 'add_note', 'value' => 'Nota interna de prueba.'],
        ]);

        app(MacroExecutorService::class)->apply($macro, $conversation);

        $note = $conversation->items()->latest('id')->first();
        $this->assertSame('Nota interna de prueba.', $note->body);
        $this->assertTrue((bool) $note->is_internal);
    }

    public function test_add_tag_resolves_the_numeric_value_as_a_tag_id(): void
    {
        $conversation = Conversation::factory()->create();
        $tag = ConversationTag::factory()->create();

        $macro = $this->macro([
            ['type' => 'add_tag', 'value' => (string) $tag->id],
        ]);

        app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertTrue($conversation->conversationTags()->whereKey($tag->id)->exists());
    }

    public function test_remove_tag_resolves_the_numeric_value_as_a_tag_id(): void
    {
        $tag = ConversationTag::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->conversationTags()->attach($tag->id);

        $macro = $this->macro([
            ['type' => 'remove_tag', 'value' => (string) $tag->id],
        ]);

        app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertFalse($conversation->conversationTags()->whereKey($tag->id)->exists());
    }

    public function test_assign_group_resolves_the_numeric_value_as_a_group_id(): void
    {
        $conversation = Conversation::factory()->create();
        // Group no expone HasFactory (GroupFactory existe pero no esta
        // enganchada al modelo), así que se crea directo con los mismos
        // campos que usaría la factory.
        $group = Group::create(['name' => 'Grupo de prueba', 'assignment_mode' => 'manual', 'default' => false]);

        $macro = $this->macro([
            ['type' => 'assign_group', 'value' => (string) $group->id],
        ]);

        app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertSame($group->id, $conversation->fresh()->group_id);
    }

    public function test_assign_agent_resolves_the_numeric_value_as_an_agent_id(): void
    {
        $conversation = Conversation::factory()->create();
        $agent = User::factory()->create();

        $macro = $this->macro([
            ['type' => 'assign_agent', 'value' => (string) $agent->id],
        ]);

        app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertSame($agent->id, $conversation->fresh()->assignee_id);
    }

    public function test_assign_agent_me_resolves_to_whoever_applies_the_macro(): void
    {
        // 'me' es el sentinel que usa HelpdeskDemoDataSeeder (el select de
        // agentes del formulario real solo ofrece ids concretos): se traduce
        // al $userId que llega a apply(), no a un id fijo guardado en la fila.
        $conversation = Conversation::factory()->create();
        $applyingAgent = User::factory()->create();

        $macro = $this->macro([
            ['type' => 'assign_agent', 'value' => 'me'],
        ], isShared: false);

        app(MacroExecutorService::class)->apply($macro, $conversation, $applyingAgent->id);

        $this->assertSame($applyingAgent->id, $conversation->fresh()->assignee_id);
    }

    public function test_change_priority_uses_the_stored_value_directly(): void
    {
        $conversation = Conversation::factory()->create(['priority' => 'low']);

        $macro = $this->macro([
            ['type' => 'change_priority', 'value' => 'urgent'],
        ]);

        app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertSame('urgent', $conversation->fresh()->priority);
    }

    public function test_change_status_uses_the_stored_value_as_the_status_slug(): void
    {
        // ChangeStatusAction::resolveStatus('open') no busca por slug: toma
        // el primer estado is_open=true por `order`, y esta suite corre
        // contra la BD real (DatabaseTransactions), donde ya existen otros
        // estados is_open=true ademas de $this->openStatus. El objetivo de
        // este test es la traduccion value->params de MacroExecutorService,
        // no el algoritmo de resolveStatus, así que se calcula el mismo
        // destino que usa la implementacion en vez de asumir uno fijo, y se
        // arranca desde un estado is_open=false recien creado para que el
        // "antes" nunca pueda coincidir por casualidad con el "despues".
        $expected = ConversationStatus::where('is_open', true)->orderBy('order')->first();
        $closedStatus = ConversationStatus::create([
            'name' => 'Cerrado de prueba', 'slug' => 'cerrado-prueba-'.uniqid(), 'is_open' => false, 'order' => 999,
        ]);
        $conversation = Conversation::factory()->create(['status_id' => $closedStatus->id]);

        $macro = $this->macro([
            ['type' => 'change_status', 'value' => 'open'],
        ]);

        app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertSame($expected->id, $conversation->fresh()->status_id);
    }

    public function test_resolve_conversation_forces_a_resolved_status_regardless_of_value(): void
    {
        ConversationStatus::firstOrCreate(
            ['slug' => 'resolved'],
            ['name' => 'Resuelto', 'is_open' => false, 'order' => 90]
        );
        // Mismo motivo que en el test anterior: se calcula el destino real
        // (is_open=false con menor `order`) en vez de asumir que la fila
        // recien creada es la que gana frente a las ya existentes.
        $expected = ConversationStatus::where('is_open', false)->orderBy('order')->first();
        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);

        // El value de resolve_conversation/close_conversation siempre es
        // null en la UI (no tienen parametros); apply() debe forzar el
        // estado igual, no depender de el.
        $macro = $this->macro([
            ['type' => 'resolve_conversation', 'value' => null],
        ]);

        app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertSame($expected->id, $conversation->fresh()->status_id);
    }

    public function test_non_numeric_value_for_an_id_based_action_does_not_crash(): void
    {
        // Dato heredado de un seeder antiguo (nombre en vez de id) o entrada
        // manual invalida: no debe reventar, solo no mutar nada.
        $conversation = Conversation::factory()->create();

        $macro = $this->macro([
            ['type' => 'assign_group', 'value' => 'Soporte Técnico N2'],
        ]);

        $result = app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertSame(['assign_group'], $result['executed']);
        $this->assertSame([], $result['failed']);
        $this->assertNull($conversation->fresh()->group_id);
    }

    public function test_explicit_params_key_still_takes_priority_over_value(): void
    {
        // Compatibilidad con AutomationEngine, que SI usa 'params'
        // estructurado: si algun dia una fila trae ambas claves, 'params'
        // gana (cubierto tambien por AutomationExecutionTransactionTest).
        $conversation = Conversation::factory()->create(['priority' => 'low']);

        $macro = $this->macro([
            ['type' => 'change_priority', 'value' => 'urgent', 'params' => ['priority' => 'high']],
        ]);

        app(MacroExecutorService::class)->apply($macro, $conversation);

        $this->assertSame('high', $conversation->fresh()->priority);
    }
}
