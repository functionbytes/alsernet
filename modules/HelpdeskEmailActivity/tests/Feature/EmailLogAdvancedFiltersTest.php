<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Modules\HelpdeskEmailActivity\Database\Seeders\HelpdeskEmailActivityPermissionsSeeder;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Tests\TestCase;

/**
 * Filtros del modal "Más filtros" (ver EmailLogController::applyFilters()).
 *
 * Cada test comprueba las DOS caras del filtro: que la fila que cumple el
 * criterio aparece y que la que no cumple desaparece. Comprobar solo la
 * primera dejaría pasar un filtro que no filtra nada.
 *
 * Los fixtures usan un asunto propio y único por test en vez de contar
 * resultados: la BD comparte ~1.500 registros reales (mismo gotcha que
 * EmailLogAnalyticsTest), así que cualquier aserción sobre totales sería
 * frágil.
 */
class EmailLogAdvancedFiltersTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $filters
     */
    private function assertFilterKeeps(array $filters, EmailLog $keep, EmailLog $drop): void
    {
        $response = $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.index', $filters));

        $response->assertOk();
        $response->assertSee($keep->subject, false);
        $response->assertDontSee($drop->subject, false);
    }

    public function test_mailable_class_filter_keeps_only_that_type(): void
    {
        $keep = EmailLog::factory()->create([
            'mailable_class' => 'Fixture\\Mail\\WantedMailable',
            'subject' => 'Filtro mailable elegido',
        ]);
        $drop = EmailLog::factory()->create([
            'mailable_class' => 'Fixture\\Mail\\OtherMailable',
            'subject' => 'Filtro mailable descartado',
        ]);

        $this->assertFilterKeeps(['mailable_class' => 'Fixture\\Mail\\WantedMailable'], $keep, $drop);
    }

    public function test_recipient_domain_filter_matches_the_domain_not_the_local_part(): void
    {
        $keep = EmailLog::factory()->create([
            'to_addresses' => ['alguien@dominio-elegido.test'],
            'subject' => 'Filtro dominio elegido',
        ]);
        // El nombre de buzón contiene el dominio buscado como subcadena: si el
        // filtro no anclara en la arroba, esta fila colaría.
        $drop = EmailLog::factory()->create([
            'to_addresses' => ['dominio-elegido.test@otro.test'],
            'subject' => 'Filtro dominio descartado',
        ]);

        $this->assertFilterKeeps(['recipient_domain' => 'dominio-elegido.test'], $keep, $drop);
    }

    public function test_has_error_filter_is_not_the_same_as_status_failed(): void
    {
        // Un rebote CON mensaje de error entra; un fallido SIN mensaje, no —
        // que es justo lo que distingue este filtro de status=failed.
        $keep = EmailLog::factory()->create([
            'status' => EmailStatus::Bounced,
            'error_message' => '550 5.1.1 mailbox unavailable',
            'subject' => 'Filtro error elegido',
        ]);
        $drop = EmailLog::factory()->create([
            'status' => EmailStatus::Failed,
            'error_message' => null,
            'subject' => 'Filtro error descartado',
        ]);

        $this->assertFilterKeeps(['has_error' => '1'], $keep, $drop);
    }

    public function test_linked_filter_separates_entities_from_loose_logs(): void
    {
        $linked = EmailLog::factory()->create([
            'entity_type' => 'Fixture\\Models\\Thing',
            'entity_id' => 4242,
            'subject' => 'Filtro vinculado elegido',
        ]);
        $loose = EmailLog::factory()->create([
            'entity_type' => null,
            'entity_id' => null,
            'subject' => 'Filtro vinculado descartado',
        ]);

        $this->assertFilterKeeps(['linked' => '1'], $linked, $loose);
        // Y al revés, para que '0' no sea un alias silencioso de "no filtrar".
        $this->assertFilterKeeps(['linked' => '0'], $loose, $linked);
    }

    public function test_tracked_filter_separates_measured_sends(): void
    {
        $tracked = EmailLog::factory()->tracked()->create(['subject' => 'Filtro seguimiento elegido']);
        $untracked = EmailLog::factory()->create([
            'metadata' => ['has_html' => true],
            'subject' => 'Filtro seguimiento descartado',
        ]);

        $this->assertFilterKeeps(['tracked' => '1'], $tracked, $untracked);
        $this->assertFilterKeeps(['tracked' => '0'], $untracked, $tracked);
    }

    public function test_stale_filter_only_keeps_old_queued_logs(): void
    {
        $stale = EmailLog::factory()->queued()->create([
            'created_at' => Carbon::now()->subDays(30),
            'subject' => 'Filtro estancado elegido',
        ]);
        // Recién encolado: en cola, sí, pero no estancado.
        $fresh = EmailLog::factory()->queued()->create([
            'created_at' => Carbon::now(),
            'subject' => 'Filtro estancado descartado',
        ]);

        $this->assertFilterKeeps(['stale' => '1'], $stale, $fresh);
    }

    public function test_an_empty_filter_value_does_not_filter_anything(): void
    {
        // El formulario envía TODOS los campos, también los vacíos: un filtro
        // que tratara '' como valor dejaría el listado en blanco.
        $log = EmailLog::factory()->create(['subject' => 'Filtro vacio no filtra']);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.index', [
                'mailable_class' => '',
                'recipient_domain' => '',
                'linked' => '',
                'tracked' => '',
                'has_error' => '',
                'stale' => '',
            ]))
            ->assertOk()
            ->assertSee($log->subject, false);
    }
}
