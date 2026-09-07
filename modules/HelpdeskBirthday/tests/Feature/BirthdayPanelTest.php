<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskEmailActivity\Models\EmailSuppression;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Humo del panel: que las pantallas renderizan de verdad y que pausar/reanudar
 * hace lo que dice.
 */
class BirthdayPanelTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected $connectionsToTransact = [null, 'helpdesk', 'mysql', 'mariadb'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->seedHelpdeskRoles();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-settings');

        // campaign_date es UNIQUE y estos tests crean la campaña de hoy: si el
        // entorno ya tiene una (el scheduler la crea cada mañana), el insert
        // reventaría. El borrado va dentro de la transacción del test, así que
        // la campaña real se restaura al terminar.
        BirthdayCampaign::whereDate('campaign_date', now()->toDateString())->delete();
    }

    public function test_el_listado_de_campanas_carga(): void
    {
        $campaign = $this->campaign();

        $this->actingAs($this->admin)
            ->get(route('helpdeskbirthday.campaigns.index'))
            ->assertOk()
            ->assertSee($campaign->campaign_date->format('d/m/Y'))
            // El listado enseña cuántos bonos se emitieron, no un código único:
            // cada cliente tiene el suyo.
            ->assertSee('Bonos');
    }

    public function test_el_detalle_muestra_el_bono_de_cada_uno_el_ritmo_y_los_destinatarios(): void
    {
        $campaign = $this->campaign();
        $this->recipient($campaign, 'ana@ejemplo.test');

        $this->actingAs($this->admin)
            ->get(route('helpdeskbirthday.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('ana@ejemplo.test')
            // El bono es de esa persona, no de la campaña.
            ->assertSee('910001-AAA')
            ->assertSee('Bonos emitidos')
            ->assertSee('09:00–14:00');
    }

    public function test_la_previsualizacion_devuelve_el_correo_renderizado(): void
    {
        $campaign = $this->campaign();
        $this->recipient($campaign, 'ana@ejemplo.test');

        $response = $this->actingAs($this->admin)
            ->get(route('helpdeskbirthday.campaigns.preview', $campaign));

        $response->assertOk();
        $this->assertStringContainsString('910001-AAA', $response->getContent());
    }

    public function test_pausar_y_reanudar_desde_el_panel(): void
    {
        $campaign = $this->campaign();

        $this->actingAs($this->admin)
            ->post(route('helpdeskbirthday.campaigns.pause', $campaign))
            ->assertRedirect();

        $this->assertSame(BirthdayCampaign::STATUS_PAUSED, $campaign->fresh()->status);

        $this->actingAs($this->admin)
            ->post(route('helpdeskbirthday.campaigns.resume', $campaign))
            ->assertRedirect();

        $this->assertSame(BirthdayCampaign::STATUS_SCHEDULED, $campaign->fresh()->status);
    }

    public function test_cancelar_descarta_los_envios_pendientes(): void
    {
        $campaign = $this->campaign();
        $recipient = $this->recipient($campaign, 'ana@ejemplo.test');

        $this->actingAs($this->admin)
            ->post(route('helpdeskbirthday.campaigns.cancel', $campaign))
            ->assertRedirect();

        $this->assertSame(BirthdayCampaign::STATUS_CANCELLED, $campaign->fresh()->status);
        $this->assertSame(BirthdayRecipient::STATUS_SKIPPED, $recipient->fresh()->status);
        $this->assertSame('cancelled', $recipient->fresh()->skip_reason);
    }

    public function test_se_puede_ver_el_correo_de_un_destinatario_concreto(): void
    {
        $campaign = $this->campaign();
        $recipient = $this->recipient($campaign, 'ana@ejemplo.test');

        $response = $this->actingAs($this->admin)
            ->get(route('helpdeskbirthday.campaigns.recipient-email', [$campaign, $recipient]));

        $response->assertOk();
        $this->assertStringContainsString('910001-AAA', $response->getContent());
        $this->assertStringContainsString('Ana', $response->getContent());
    }

    public function test_no_se_puede_ver_el_correo_de_un_destinatario_de_otra_campana(): void
    {
        $campaign = $this->campaign();
        // Fecha lejana: campaign_date es UNIQUE y el entorno puede tener
        // campañas reales en los días cercanos.
        $other = BirthdayCampaign::create([
            'campaign_date' => '2001-01-01',
            'status' => BirthdayCampaign::STATUS_COMPLETED,
            'template_key' => 'birthday-coupon',
        ]);
        $recipient = $this->recipient($other, 'ajena@ejemplo.test');

        $this->actingAs($this->admin)
            ->get(route('helpdeskbirthday.campaigns.recipient-email', [$campaign, $recipient]))
            ->assertNotFound();
    }

    public function test_dar_de_baja_a_un_destinatario_lo_suprime_y_lo_omite(): void
    {
        $campaign = $this->campaign();
        $recipient = $this->recipient($campaign, 'ana@ejemplo.test');

        $this->actingAs($this->admin)
            ->post(route('helpdeskbirthday.campaigns.recipient-unsubscribe', [$campaign, $recipient]))
            ->assertRedirect();

        $this->assertTrue(
            EmailSuppression::isSuppressed('ana@ejemplo.test', 'HelpdeskBirthday')
        );
        $this->assertSame(BirthdayRecipient::STATUS_SKIPPED, $recipient->fresh()->status);
    }

    public function test_solo_se_reintentan_los_envios_fallidos(): void
    {
        $campaign = $this->campaign();
        $recipient = $this->recipient($campaign, 'ana@ejemplo.test');

        // Pendiente: no se reintenta.
        $this->actingAs($this->admin)
            ->post(route('helpdeskbirthday.campaigns.recipient-retry', [$campaign, $recipient]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $recipient->update(['status' => BirthdayRecipient::STATUS_FAILED]);

        $this->actingAs($this->admin)
            ->post(route('helpdeskbirthday.campaigns.recipient-retry', [$campaign, $recipient]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(BirthdayRecipient::STATUS_PENDING, $recipient->fresh()->status);
    }

    public function test_el_listado_muestra_el_panel_de_estadisticas(): void
    {
        $this->campaign();

        $this->actingAs($this->admin)
            ->get(route('helpdeskbirthday.campaigns.index'))
            ->assertOk()
            ->assertSee('Tasa de apertura')
            ->assertSee('Envíos por día')
            ->assertSee('Campaña de hoy');
    }

    public function test_la_pantalla_de_ajustes_carga(): void
    {
        $this->actingAs($this->admin)
            ->get(route('helpdeskbirthday.settings.index'))
            ->assertOk()
            ->assertSee('Ritmo de envío')
            ->assertSee('Excluir a quien rechazó información comercial');
    }

    public function test_la_pantalla_de_canjes_carga(): void
    {
        $campaign = $this->campaign();

        $response = $this->actingAs($this->admin)
            ->get(route('helpdeskbirthday.campaigns.redemptions', $campaign));

        // «Bonos» y no «cupón»: Gestión emite uno por cliente, y la pantalla
        // habla de los bonos de la campaña, no de un código único.
        $response->assertOk()->assertSee('Canjes de los bonos', false);

        // Sin BD de PrestaShop configurada avisa en vez de mostrar ceros; con
        // ella, pinta la tabla. Ambas salidas son válidas según el entorno.
        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'Pedidos con bono')
                || str_contains($content, 'No hay base de datos de PrestaShop configurada'),
            'La pantalla de canjes no muestra ni la tabla ni el aviso de PrestaShop no configurado.'
        );
    }

    public function test_un_usuario_sin_permisos_no_entra(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('helpdeskbirthday.campaigns.index'))
            ->assertForbidden();
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function campaign(): BirthdayCampaign
    {
        return BirthdayCampaign::create([
            'campaign_date' => now()->toDateString(),
            'status' => BirthdayCampaign::STATUS_SCHEDULED,
            'template_key' => 'birthday-coupon',
            'window_start' => '09:00:00',
            'window_end' => '14:00:00',
            'throttle_per_hour' => 600,
            'interval_seconds' => 180,
            'recipients_total' => 1,
        ]);
    }

    private function recipient(BirthdayCampaign $campaign, string $email): BirthdayRecipient
    {
        return BirthdayRecipient::create([
            'campaign_id' => $campaign->id,
            'erp_customer_id' => '1',
            'email' => $email,
            'name' => 'Ana',
            'birth_date' => '1990-01-01',
            'scheduled_at' => now()->addMinutes(5),
            'status' => BirthdayRecipient::STATUS_PENDING,
            // Su bono, emitido por gestión: es el que lleva su correo.
            'coupon_code' => '910001',
            'coupon_verification_code' => 'AAA',
            'coupon_amount' => 5,
            'coupon_min_purchase' => 30,
            'coupon_valid_from' => now()->toDateString(),
            'coupon_valid_to' => now()->addMonth()->toDateString(),
        ]);
    }
}
