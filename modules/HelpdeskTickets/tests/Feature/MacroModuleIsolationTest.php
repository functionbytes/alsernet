<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Macro as ConversationMacro;
use Modules\HelpdeskTickets\Models\Macro as TicketMacro;
use Tests\TestCase;

/**
 * `helpdesk_macros` la comparten dos modelos con vocabularios de accion
 * incompatibles. La columna `module` mas el global scope de cada modelo son
 * lo unico que impide que un panel liste, edite, borre o aplique las macros
 * del otro; esta clase fija ese contrato.
 */
class MacroModuleIsolationTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function ticketMacro(string $name = 'Macro de ticket'): TicketMacro
    {
        $macro = new TicketMacro([
            'name' => $name,
            'actions' => [['type' => 'reply', 'body' => 'Hola']],
        ]);
        $macro->save();

        return $macro;
    }

    private function conversationMacro(string $name = 'Macro de conversacion'): ConversationMacro
    {
        $macro = new ConversationMacro([
            'name' => $name,
            'actions' => [['type' => 'send_reply', 'params' => ['body' => 'Hola']]],
        ]);
        $macro->save();

        return $macro;
    }

    public function test_creating_seals_the_owning_module(): void
    {
        $this->assertSame('tickets', $this->ticketMacro()->module);
        $this->assertSame('helpdesk', $this->conversationMacro()->module);
    }

    public function test_module_cannot_be_forced_through_mass_assignment(): void
    {
        $macro = new ConversationMacro([
            'name' => 'Intento de suplantacion',
            'module' => 'tickets',
            'actions' => [['type' => 'send_reply', 'params' => []]],
        ]);
        $macro->save();

        $this->assertSame('helpdesk', $macro->fresh()->module);
    }

    public function test_each_model_only_sees_its_own_rows(): void
    {
        $ticket = $this->ticketMacro();
        $conversation = $this->conversationMacro();

        $this->assertTrue(TicketMacro::query()->whereKey($ticket->id)->exists());
        $this->assertFalse(TicketMacro::query()->whereKey($conversation->id)->exists());

        $this->assertTrue(ConversationMacro::query()->whereKey($conversation->id)->exists());
        $this->assertFalse(ConversationMacro::query()->whereKey($ticket->id)->exists());
    }

    public function test_route_binding_cannot_reach_the_other_module(): void
    {
        $ticket = $this->ticketMacro();
        $conversation = $this->conversationMacro();

        $this->assertNull(TicketMacro::find($conversation->id));
        $this->assertNull(ConversationMacro::find($ticket->id));
    }

    public function test_bulk_delete_by_id_cannot_reach_the_other_module(): void
    {
        $ticket = $this->ticketMacro();
        $conversation = $this->conversationMacro();

        // Lo que hace bulkAction: un whereIn con ids venidos del formulario.
        foreach (ConversationMacro::whereIn('id', [$ticket->id, $conversation->id])->get() as $macro) {
            $macro->delete();
        }

        // fresh() consulta sin scopes (ni el de origen ni el de soft delete),
        // asi que aqui hay que releer por el modelo para ver el filtro real.
        $this->assertNotNull(TicketMacro::find($ticket->id), 'La macro de ticket no debe caer en un bulk delete del panel de conversaciones');
        $this->assertNull(ConversationMacro::find($conversation->id));
    }

    public function test_counters_do_not_aggregate_the_other_module(): void
    {
        $before = TicketMacro::count();

        $this->conversationMacro('Solo para conversaciones');

        $this->assertSame($before, TicketMacro::count());
    }
}
