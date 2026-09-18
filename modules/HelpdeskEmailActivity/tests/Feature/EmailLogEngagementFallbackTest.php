<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Modules\HelpdeskEmailActivity\Database\Seeders\HelpdeskEmailActivityPermissionsSeeder;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Models\EmailLogOpen;
use Tests\TestCase;

/**
 * La pestaña "Aperturas" del detalle se decide por metadata.open_tracking_enabled
 * (ver EmailLogController::opensSummary()), que es lo que distingue "cero
 * aperturas de verdad" de "este envío nunca se midió".
 *
 * El problema: los envíos anteriores a que el listener empezara a guardar ese
 * flag tienen metadata NULL. Si el guard mirara solo el flag, sus aperturas
 * quedarían registradas en base de datos pero invisibles en la interfaz — que
 * es exactamente lo que pasaba con 92 aperturas reales de este entorno.
 */
class EmailLogEngagementFallbackTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailActivityPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemailactivity.view');
    }

    public function test_recorded_opens_are_shown_even_when_the_tracking_flag_is_missing(): void
    {
        $log = EmailLog::factory()->create([
            'metadata' => null,
            'subject' => 'Envio antiguo con apertura',
        ]);

        EmailLogOpen::query()->create([
            'email_log_id' => $log->id,
            'opened_at' => Carbon::now()->subHour(),
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.show', $log->uid))
            ->assertOk()
            // La pestaña solo se renderiza si opensSummary() no es null.
            ->assertSee('data-evx-panel="opens"', false);
    }

    public function test_a_send_without_tracking_and_without_opens_still_hides_the_tab(): void
    {
        // El otro lado del guard: sin flag y sin aperturas no se puede afirmar
        // "0 aperturas", así que la pestaña no debe aparecer.
        $log = EmailLog::factory()->create([
            'metadata' => null,
            'subject' => 'Envio antiguo sin apertura',
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.show', $log->uid))
            ->assertOk()
            ->assertDontSee('data-evx-panel="opens"', false);
    }

    public function test_a_tracked_send_shows_the_tab_with_zero_opens(): void
    {
        // Con seguimiento, "0 aperturas" SÍ es un dato real y debe verse.
        $log = EmailLog::factory()->tracked()->create(['subject' => 'Envio medido sin aperturas']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.show', $log->uid))
            ->assertOk()
            ->assertSee('data-evx-panel="opens"', false);
    }
}
