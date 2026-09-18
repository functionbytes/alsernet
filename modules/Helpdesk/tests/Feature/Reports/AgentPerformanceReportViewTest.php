<?php

namespace Modules\Helpdesk\Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Http\Controllers\Managers\AgentPerformanceController;
use Modules\Helpdesk\Models\Conversation;
use Tests\TestCase;

/**
 * AgentPerformanceController::index() construye $agents como una Collection
 * de ARRAYS (->map(fn () => [...])), pero la vista los leía con `->` (sintaxis
 * de objeto) y con dos nombres de clave que no existen (avg_first_response_seconds
 * y messages_sent, cuando el controlador produce avg_response_seconds y
 * message_count). El resultado: cada fila salía con el nombre en blanco y
 * todo a cero, con un warning de PHP por cada acceso — el informe entero
 * era ilegible en cuanto había alguna conversación cerrada este mes.
 *
 * Este test renderiza la vista real (no solo el payload del controlador),
 * que es donde vivía el defecto.
 */
class AgentPerformanceReportViewTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    public function test_view_renders_agent_data_instead_of_blanks(): void
    {
        $agent = User::factory()->create(['firstname' => 'Casimira', 'lastname' => 'Cobo']);

        $this->actingAs(User::orderBy('id')->firstOrFail());

        Conversation::factory()->create([
            'assignee_id' => $agent->id,
            'closed_at' => now(),
            'first_response_at' => now()->addMinutes(5),
        ]);

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        Cache::forget("helpdesk:reports:agents:{$from->toDateString()}:{$to->toDateString()}");

        $html = app(AgentPerformanceController::class)->index()->render();

        $this->assertStringContainsString('Casimira Cobo', $html);
        $this->assertStringContainsString('<td class="text-end">1</td>', $html);
        // El bug rellenaba silenciosamente cada celda con 0/blanco vía `->`
        // sobre un array; una fila con el nombre correcto y "1" cerrada basta
        // para probar que ya no está leyendo null en todos lados.
    }
}
