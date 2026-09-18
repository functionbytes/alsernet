<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskBirthday\Listeners\AnonymizeBirthdayRecipients;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Services\BirthdayLanguageResolver;
use Modules\HelpdeskBirthday\Services\BirthdayQueueHealthService;
use Tests\TestCase;

/**
 * GDPR, idioma del cliente y salud de la cola.
 */
class BirthdayComplianceAndLanguageTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk', 'mysql', 'mariadb'];

    protected function setUp(): void
    {
        parent::setUp();

        BirthdayCampaign::whereDate('campaign_date', now()->toDateString())->delete();
    }

    /* ── GDPR ────────────────────────────────────────────────────────────── */

    public function test_el_borrado_gdpr_anonimiza_el_dato_personal_pero_conserva_el_envio(): void
    {
        $campaign = $this->campaign();
        $recipient = BirthdayRecipient::create([
            'campaign_id' => $campaign->id,
            'erp_customer_id' => '9999',
            'email' => 'BorrarMe@ejemplo.test',
            'name' => 'Nombre Apellido',
            'birth_date' => '1990-05-05',
            'status' => BirthdayRecipient::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $customer = new Customer(['email' => 'borrarme@ejemplo.test']);
        $customer->id = 1;

        (new AnonymizeBirthdayRecipients)->handle(new CustomerGdprDeleted(
            customer: $customer,
            hard: true,
            conversationIds: [],
            result: [],
            customerEmail: 'borrarme@ejemplo.test',
            customerPhones: [],
        ));

        $recipient->refresh();

        // El dato personal desaparece...
        $this->assertStringNotContainsString('borrarme', $recipient->email);
        $this->assertStringContainsString('@anonimo.local', $recipient->email);
        $this->assertNull($recipient->name);
        $this->assertNull($recipient->birth_date);
        $this->assertNull($recipient->erp_customer_id);

        // ...pero el hecho de que se envió, no: los contadores siguen cuadrando.
        $this->assertSame(BirthdayRecipient::STATUS_SENT, $recipient->status);
        $this->assertNotNull($recipient->sent_at);
        $this->assertSame(1, $campaign->recipients()->count());
    }

    public function test_el_borrado_gdpr_no_toca_a_otros_destinatarios(): void
    {
        $campaign = $this->campaign();
        $otro = BirthdayRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'otro@ejemplo.test',
            'name' => 'Otro',
            'status' => BirthdayRecipient::STATUS_SENT,
        ]);

        $customer = new Customer(['email' => 'borrarme@ejemplo.test']);
        $customer->id = 1;

        (new AnonymizeBirthdayRecipients)->handle(new CustomerGdprDeleted(
            customer: $customer, hard: true, conversationIds: [], result: [],
            customerEmail: 'borrarme@ejemplo.test', customerPhones: [],
        ));

        $this->assertSame('otro@ejemplo.test', $otro->fresh()->email);
        $this->assertSame('Otro', $otro->fresh()->name);
    }

    /* ── Idioma ──────────────────────────────────────────────────────────── */

    public function test_sin_mapa_configurado_todos_reciben_el_idioma_por_defecto(): void
    {
        config(['helpdeskbirthday.erp_language_map' => [], 'helpdeskbirthday.fallback_language' => 'es']);

        $resolver = app(BirthdayLanguageResolver::class);

        $this->assertSame('es', $resolver->isoFor(7));
        $this->assertSame('es', $resolver->isoFor(null));
    }

    public function test_el_mapa_del_erp_decide_el_idioma(): void
    {
        config([
            'helpdeskbirthday.erp_language_map' => [2 => 'pt'],
            'helpdeskbirthday.fallback_language' => 'es',
        ]);

        $resolver = app(BirthdayLanguageResolver::class);

        // 'pt' existe en la tabla langs de este entorno.
        $this->assertSame('pt', $resolver->isoFor(2));
        $this->assertSame('es', $resolver->isoFor(99));
    }

    public function test_un_idioma_que_no_existe_en_mailer_cae_al_por_defecto(): void
    {
        config([
            'helpdeskbirthday.erp_language_map' => [3 => 'zz'],
            'helpdeskbirthday.fallback_language' => 'es',
        ]);

        // Sin traducción para 'zz' el correo saldría en blanco: mejor el
        // idioma por defecto.
        $this->assertSame('es', app(BirthdayLanguageResolver::class)->isoFor(3));
    }

    /* ── Salud de la cola ────────────────────────────────────────────────── */

    public function test_detecta_envios_reservados_que_nadie_procesa(): void
    {
        $campaign = $this->campaign();
        $recipient = BirthdayRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'atascado@ejemplo.test',
            'status' => BirthdayRecipient::STATUS_SENDING,
        ]);

        // Eloquent pisa updated_at al crear, así que el "lleva rato atascado"
        // hay que forzarlo por query.
        BirthdayRecipient::whereKey($recipient->id)->update(['updated_at' => now()->subHour()]);

        $health = app(BirthdayQueueHealthService::class)->check();

        $this->assertFalse($health['healthy']);
        $this->assertSame(1, $health['stuck_sending']);
    }

    public function test_sin_atascos_reporta_salud_correcta(): void
    {
        $campaign = $this->campaign();
        BirthdayRecipient::create([
            'campaign_id' => $campaign->id,
            'email' => 'ok@ejemplo.test',
            'status' => BirthdayRecipient::STATUS_SENT,
        ]);

        $this->assertTrue(app(BirthdayQueueHealthService::class)->check()['healthy']);
    }

    private function campaign(): BirthdayCampaign
    {
        return BirthdayCampaign::create([
            'campaign_date' => now()->toDateString(),
            'status' => BirthdayCampaign::STATUS_SCHEDULED,
            'coupon_code' => 'X-1',
            'template_key' => 'birthday-coupon',
        ]);
    }
}
