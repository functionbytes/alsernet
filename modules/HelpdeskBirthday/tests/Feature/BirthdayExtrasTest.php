<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Modules\HelpdeskBirthday\Mail\BirthdayCouponMailable;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Notifications\BirthdayCampaignFailedNotification;
use Modules\HelpdeskBirthday\Services\BirthdayCampaignService;
use Modules\HelpdeskBirthday\Services\BirthdayTestSendService;
use Modules\HelpdeskBirthday\Support\BirthdaySettings;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Aviso de campaña abortada, envío de prueba y reintento masivo.
 */
class BirthdayExtrasTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected $connectionsToTransact = [null, 'helpdesk', 'mysql', 'mariadb'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'array']);

        $this->seedHelpdeskRoles();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-settings');
        // El permiso, explícito. Antes bastaba el rol porque Auth registraba un
        // Gate::before que concedía TODO a 'super-settings'; retirado el
        // 7-sep-2026, el rol ya no concede nada por sí mismo y el aviso de
        // campaña abortada —que filtra a sus destinatarios por
        // can('helpdeskbirthday.manage')— no habría encontrado a nadie.
        $this->admin->givePermissionTo('helpdeskbirthday.manage');

        BirthdayCampaign::whereDate('campaign_date', now()->toDateString())->delete();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_avisa_cuando_la_campana_se_aborta(): void
    {
        Notification::fake();

        // Sin tipo de bono configurado la campaña queda en pausa (no envía), y
        // de eso hay que avisar igual: el cliente se queda sin su felicitación
        // y el día no se repite.
        config()->set('helpdeskbirthday.customers_api_url', 'http://manager.test');

        // La campaña se prepara de verdad, así que hay que servirle la
        // audiencia: sin fake, la petición sale a la red del test.
        Http::fake([
            'manager.test/*' => Http::response([
                'success' => true,
                'data' => [],
                'pagination' => ['limit' => 100, 'offset' => 0, 'count' => 0, 'hasMore' => false],
            ]),
        ]);

        $this->app->instance(BirthdaySettings::class, $this->settingsWithoutCoupon());

        app(BirthdayCampaignService::class)->prepare(now()->toImmutable());

        Notification::assertSentTo(
            $this->admin,
            BirthdayCampaignFailedNotification::class,
            function (BirthdayCampaignFailedNotification $n): bool {
                return str_contains($n->reason, 'tipo de bono');
            }
        );
    }

    public function test_el_envio_de_prueba_manda_el_correo_sin_tocar_la_campana(): void
    {
        Mail::fake();

        $result = app(BirthdayTestSendService::class)->send(['yo@empresa.test']);

        $this->assertSame(['yo@empresa.test'], $result['sent']);
        Mail::assertSent(BirthdayCouponMailable::class, fn ($m) => $m->hasTo('yo@empresa.test'));

        // No se crea campaña ni destinatario de verdad.
        $this->assertSame(0, BirthdayCampaign::whereDate('campaign_date', now()->toDateString())->count());
        $this->assertSame(0, BirthdayRecipient::where('email', 'yo@empresa.test')->count());
    }

    public function test_el_envio_de_prueba_rechaza_direcciones_invalidas(): void
    {
        Mail::fake();

        $result = app(BirthdayTestSendService::class)->send(['no-es-un-email']);

        $this->assertSame([], $result['sent']);
        $this->assertArrayHasKey('no-es-un-email', $result['failed']);
        Mail::assertNothingSent();
    }

    public function test_el_panel_limita_el_numero_de_direcciones_de_prueba(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)
            ->post(route('helpdeskbirthday.settings.test-send'), [
                'test_emails' => 'a@t.test b@t.test c@t.test d@t.test e@t.test f@t.test',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_reintento_masivo_devuelve_todos_los_fallidos_a_la_cola(): void
    {
        $campaign = BirthdayCampaign::create([
            'campaign_date' => now()->toDateString(),
            'status' => BirthdayCampaign::STATUS_COMPLETED,
            'coupon_code' => 'X-1',
            'template_key' => 'birthday-coupon',
            'recipients_total' => 2,
            'failed_count' => 2,
            'finished_at' => now(),
        ]);

        foreach (['a@t.test', 'b@t.test'] as $email) {
            BirthdayRecipient::create([
                'campaign_id' => $campaign->id,
                'email' => $email,
                'status' => BirthdayRecipient::STATUS_FAILED,
                'error_message' => 'timeout',
            ]);
        }

        $this->actingAs($this->admin)
            ->post(route('helpdeskbirthday.campaigns.retry-failed', $campaign))
            ->assertRedirect()
            ->assertSessionHas('success');

        $campaign->refresh();

        $this->assertSame(0, $campaign->failed_count);
        // La campaña cerrada vuelve a activa para que dispatch-due los recoja.
        $this->assertSame(BirthdayCampaign::STATUS_SCHEDULED, $campaign->status);
        $this->assertSame(2, $campaign->recipients()->where('status', BirthdayRecipient::STATUS_PENDING)->count());
        $this->assertNull($campaign->recipients()->first()->error_message);
    }

    public function test_sin_fallidos_el_reintento_masivo_avisa(): void
    {
        $campaign = BirthdayCampaign::create([
            'campaign_date' => now()->toDateString(),
            'status' => BirthdayCampaign::STATUS_COMPLETED,
            'template_key' => 'birthday-coupon',
        ]);

        $this->actingAs($this->admin)
            ->post(route('helpdeskbirthday.campaigns.retry-failed', $campaign))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_la_policy_respeta_el_estado_de_la_campana(): void
    {
        $completed = BirthdayCampaign::create([
            'campaign_date' => now()->toDateString(),
            'status' => BirthdayCampaign::STATUS_COMPLETED,
            'template_key' => 'birthday-coupon',
        ]);

        // Un usuario con el permiso y sin roles: prueba la Policy en sí, sin
        // depender de lo que un rol arrastre consigo.
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdeskbirthday.manage', 'helpdeskbirthday.view');

        $this->assertTrue($manager->can('manage', BirthdayCampaign::class));
        $this->assertTrue($manager->can('view', $completed));

        // Tiene el permiso, pero una campaña cerrada ya no se pausa.
        $this->assertFalse($manager->can('pause', $completed));
    }

    private function settingsWithoutCoupon(): BirthdaySettings
    {
        return new class extends BirthdaySettings
        {
            public function all(): array
            {
                return [
                    'window_start' => '09:00', 'window_end' => '14:00',
                    'throttle_per_hour' => 600, 'max_recipients' => 2000,
                    'leap_day_policy' => 'feb28', 'template_key' => 'birthday-coupon',
                    'coupon_code' => '', 'coupon_verification_code' => '',
                    'coupon_valid_from' => '', 'coupon_valid_to' => '',
                    'coupon_amount' => '', 'coupon_min_purchase' => '',
                    'validate_against_erp' => false,
                    'commercial_optin' => true, 'lopd_accepted' => false,
                    'has_email' => true, 'check_suppressions' => true,
                ];
            }
        };
    }
}
