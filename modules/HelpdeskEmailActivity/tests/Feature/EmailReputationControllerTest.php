<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmailReputationControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('helpdeskemailactivity.view', 'web');
        Permission::findOrCreate('helpdeskemailactivity.manage', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo('helpdeskemailactivity.view');
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('helpdeskemailactivity.reputation.index'))->assertRedirect();
    }

    public function test_index_requires_view_permission(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('helpdeskemailactivity.reputation.index'))
            ->assertForbidden();
    }

    public function test_index_renders_with_no_domains_configured(): void
    {
        Setting::set('helpdeskemailactivity.reputation_domains', '[]');

        $this->actingAs($this->viewer)
            ->get(route('helpdeskemailactivity.reputation.index'))
            ->assertOk()
            ->assertSee('Sin dominios configurados');
    }

    public function test_index_computes_per_domain_rate_over_terminal_attempts_only(): void
    {
        // Dominio de prueba propio (RFC 2606, reservado para documentación) —
        // acotar por dominio en la aserción evita que la contaminación real
        // de email_logs de otras corridas/sesiones (fuera de esta
        // transacción, con dominios @company.com de Faker) infle el conteo:
        // ver EmailLog::reputationStats(), que sin dominio agrega TODO el
        // histórico y por eso no es una aserción fiable en este entorno.
        Setting::set('helpdeskemailactivity.reputation_domains', json_encode([
            ['domain' => 'reputation-fixture.test', 'dkim_selectors' => []],
        ]));

        // 2 sent + 1 bounced = 3 intentos terminales, 1 rebotado = 33.33%.
        // El 'queued' NO debe contar en el denominador.
        EmailLog::factory()->count(2)->create(['status' => EmailStatus::Sent, 'from_address' => 'a@reputation-fixture.test']);
        EmailLog::factory()->create(['status' => EmailStatus::Bounced, 'from_address' => 'b@reputation-fixture.test', 'sent_at' => now()]);
        EmailLog::factory()->queued()->create(['from_address' => 'c@reputation-fixture.test']);

        $this->actingAs($this->viewer)
            ->get(route('helpdeskemailactivity.reputation.index'))
            ->assertOk()
            ->assertViewHas('rows', function ($rows) {
                $row = $rows->firstWhere('domain', 'reputation-fixture.test');

                return $row !== null
                    && $row['stats']['attempted'] === 3
                    && $row['stats']['bounce_rate'] === 33.33;
            });
    }

    public function test_refresh_requires_manage_permission(): void
    {
        $this->actingAs($this->viewer)
            ->post(route('helpdeskemailactivity.reputation.refresh'), ['domain' => 'example.test'])
            ->assertForbidden();
    }

    public function test_manager_can_refresh_a_domain(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['helpdeskemailactivity.view', 'helpdeskemailactivity.manage']);

        $this->actingAs($manager)
            ->post(route('helpdeskemailactivity.reputation.refresh'), ['domain' => 'example.test'])
            ->assertRedirect();
    }
}
