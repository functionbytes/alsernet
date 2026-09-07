<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketDetailDataController;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Nombres de personas en el historial del ticket.
 *
 * Dos sitios distintos dejaban el historial ilegible, por la misma causa:
 * el User de esta app guarda firstname/lastname y NO tiene columna `name`.
 * Uno escribía "Usuario #1" como asignatario, el otro publicaba el autor
 * del cambio como null y la pestaña Actividad salía entera sin autor.
 *
 * Se prueban los dos métodos directamente en vez de a través del log:
 * `$ticket->activities()` resuelve por la conexión 'helpdesk' mientras
 * spatie/activitylog escribe por la default ('mysql'), así que dentro de
 * DatabaseTransactions una lectura nunca ve sus propias escrituras.
 */
class TicketActivityAuthorLabelsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private function assigneeLabel(mixed $id): string
    {
        $method = new \ReflectionMethod(Ticket::class, 'activityAssigneeLabel');
        $method->setAccessible(true);

        return $method->invoke(new Ticket, $id);
    }

    private function causerName(?object $causer): ?string
    {
        $method = new \ReflectionMethod(TicketDetailDataController::class, 'causerName');
        $method->setAccessible(true);

        return $method->invoke(app(TicketDetailDataController::class), $causer);
    }

    // ─── asignatario en la descripción del historial ─────────────────────────

    public function test_nombra_al_agente_disponible_desde_el_catalogo(): void
    {
        $role = Role::firstOrCreate(['name' => 'helpdesk-agent', 'guard_name' => 'web']);
        $agente = User::factory()->create([
            'firstname' => 'Casilda',
            'lastname' => 'Verdemar',
            'available' => true,
        ]);
        $agente->assignRole($role);
        CatalogCacheService::invalidate();

        $this->assertSame('Casilda Verdemar', $this->assigneeLabel($agente->id));
    }

    public function test_nombra_al_asignatario_aunque_ya_no_sea_un_agente_disponible(): void
    {
        // agents() filtra por rol helpdesk-agent + available=true. Un
        // asignatario fuera de esa lista dejaba "Usuario #7" en la bitácora,
        // justo en los tickets viejos, que son los que más se auditan.
        $ajeno = User::factory()->create([
            'firstname' => 'Nicomedes',
            'lastname' => 'Sinrol',
            'available' => false,
        ]);

        CatalogCacheService::invalidate();

        $this->assertNull(CatalogCacheService::agents()->firstWhere('id', $ajeno->id));
        $this->assertSame('Nicomedes Sinrol', $this->assigneeLabel($ajeno->id));
    }

    public function test_sin_asignatario_dice_sin_asignar(): void
    {
        $this->assertSame('Sin asignar', $this->assigneeLabel(null));
    }

    public function test_un_id_que_ya_no_existe_conserva_el_texto_de_respaldo(): void
    {
        // Un usuario borrado de verdad: no hay nombre que recuperar, pero el
        // id sigue diciendo algo a quien audita.
        $this->assertSame('Usuario #99999999', $this->assigneeLabel(99999999));
    }

    // ─── autor del cambio en el payload ──────────────────────────────────────

    public function test_publica_el_nombre_completo_del_autor(): void
    {
        $user = User::factory()->create(['firstname' => 'Casilda', 'lastname' => 'Verdemar']);

        $this->assertSame('Casilda Verdemar', $this->causerName($user));
    }

    public function test_sin_autor_no_inventa_ninguno(): void
    {
        $this->assertNull($this->causerName(null));
    }

    public function test_un_autor_sin_nombre_cae_al_correo(): void
    {
        $user = User::factory()->create([
            'firstname' => '',
            'lastname' => '',
            'email' => 'anonimo-'.uniqid().'@example.invalid',
        ]);

        $this->assertSame($user->email, $this->causerName($user));
    }
}
