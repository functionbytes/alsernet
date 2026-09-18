<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Services\MentionService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * resolveMentions() se extrajo de notifyMentions() para que el panel de
 * notas pueda pintar el bloque "Menciones".
 *
 * La mención no se guarda en ninguna columna: se deduce del texto de la
 * nota. Por eso el panel y la notificación TIENEN que compartir este
 * servicio — si cada uno parseara por su cuenta, el panel podría anunciar
 * menciones a gente que nunca recibió el aviso.
 */
class MentionServiceResolveTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private MentionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MentionService::class);
    }

    private function makeAgent(string $firstname, string $lastname): User
    {
        $role = Role::firstOrCreate(['name' => 'helpdesk-agent', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'available' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_resuelve_una_mencion_al_agente_correcto(): void
    {
        $agent = $this->makeAgent('Abelarda', 'Cronix');

        $found = $this->service->resolveMentions('Confirma esto @Abelarda Cronix, por favor.');

        $this->assertCount(1, $found);
        $this->assertSame($agent->id, $found->first()->id);
    }

    public function test_no_confunde_una_direccion_de_correo_con_una_mencion(): void
    {
        $this->makeAgent('Abelarda', 'Cronix');

        // El bloque "Menciones" llegó a mostrar "@construcinsa.mx" como si
        // fuera un compañero, porque el parseo en crudo del JS trataba
        // cualquier @ del texto como mención.
        $found = $this->service->resolveMentions('Copiar a compras@construcinsa.mx en las respuestas.');

        $this->assertCount(0, $found);
    }

    public function test_no_repite_al_mismo_agente_mencionado_dos_veces(): void
    {
        $this->makeAgent('Abelarda', 'Cronix');

        $found = $this->service->resolveMentions("@Abelarda Cronix, mira esto.\n@Abelarda Cronix, urgente.");

        $this->assertCount(1, $found);
    }

    public function test_ignora_a_quien_no_es_agente_disponible(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Bartola',
            'lastname' => 'Nomisma',
            'available' => true,
        ]);

        // Sin el rol helpdesk-agent no es del equipo: mencionarle no debe
        // resolverlo (mismo criterio que CatalogCacheService::agents()).
        $this->assertCount(0, $this->service->resolveMentions('@Bartola Nomisma revisa esto.'));
        $this->assertNotNull($user->id);
    }

    public function test_un_texto_sin_arrobas_no_resuelve_nada(): void
    {
        $this->makeAgent('Abelarda', 'Cronix');

        $this->assertCount(0, $this->service->resolveMentions('Sin menciones aquí.'));
    }
}
