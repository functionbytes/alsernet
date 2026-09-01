<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskEmailLog\Database\Seeders\HelpdeskEmailLogPermissionsSeeder;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Tests\TestCase;

/**
 * Selector de modo de vista (Lista/Hilos/Compacta/Kanban) traído desde la
 * bandeja de tickets ya retirada (/tickets/emails) y adaptado a esta página
 * genérica de auditoría (/panel/helpdeskemaillog) — ver evx-mode-switch en
 * modules/HelpdeskEmailLog/resources/views/emails/index.blade.php.
 */
class EmailLogViewModesTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: EmailLog vive en la conexión default de la app
    // (mismo gotcha ya documentado en EmailLogControllerTest de este módulo).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailLogPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemaillog.view');
    }

    public function test_index_includes_the_four_mode_view_switch(): void
    {
        EmailLog::factory()->create();

        $response = $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index'));

        $response->assertOk();

        // Contenedor del selector + los 4 modos, sus contenedores de vista y
        // la utilidad de ocultar, exactamente como los define el contrato
        // compartido con el CSS (emaillog.css).
        $response->assertSee('id="evx-mode-switch"', false);
        $response->assertSee('data-evx-mode="list"', false);
        $response->assertSee('data-evx-mode="thread"', false);
        $response->assertSee('data-evx-mode="compact"', false);
        $response->assertSee('data-evx-mode="kanban"', false);
        $response->assertSee('id="evx-list-view"', false);
        $response->assertSee('id="evx-thread-view"', false);
        $response->assertSee('id="evx-kanban-view"', false);
        $response->assertSee('evx-mode-hidden', false);

        // Etiquetas visibles de los 4 botones (locale 'es' por defecto).
        $response->assertSee(__('helpdeskemaillog::emaillog.view_modes.list'));
        $response->assertSee(__('helpdeskemaillog::emaillog.view_modes.thread'));
        $response->assertSee(__('helpdeskemaillog::emaillog.view_modes.compact'));
        $response->assertSee(__('helpdeskemaillog::emaillog.view_modes.kanban'));
    }

    public function test_index_embeds_current_page_rows_as_json_for_the_view_modes(): void
    {
        $log = EmailLog::factory()->create([
            'subject' => 'Asunto de prueba evx-mode-switch',
        ]);

        $response = $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index'));

        $response->assertOk();

        // El uid del registro real sembrado debe viajar en el JSON embebido
        // que consume el JS de Hilos/Kanban (evxModeRows) — buscamos en el
        // HTML crudo de la respuesta, no en una variable de vista.
        $response->assertSee($log->uid, false);
    }

    public function test_index_still_renders_without_any_logs(): void
    {
        // Con 0 filas, renderThreadGroups()/renderKanban() deben operar
        // sobre un array vacío sin romper la vista (rows = []).
        $response = $this->actingAs($this->viewer())
            ->get(route('helpdeskemaillog.index', ['search' => 'no-debe-existir-nada-con-esto-xyz']));

        $response->assertOk();
        $response->assertSee('id="evx-mode-switch"', false);
    }
}
