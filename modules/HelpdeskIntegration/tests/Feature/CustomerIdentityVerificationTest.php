<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskIntegration\Database\Seeders\HelpdeskIntegrationProvidersSeeder;
use Modules\HelpdeskIntegration\Jobs\SendIdentityCodeSmsJob;
use Modules\HelpdeskIntegration\Mail\CustomerIdentityCodeMail;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;
use Modules\HelpdeskIntegration\Services\CustomerIdentityVerificationService;
use Modules\HelpdeskIntegration\Support\IntegrationDriverRegistry;
use Modules\HelpdeskIntegration\Tests\Support\FakeIntegrationDriver;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

class CustomerIdentityVerificationTest extends HelpdeskTestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create(['email' => 'test@example.com']);
    }

    // ─── show() antes de verificar ─────────────────────────────────────────────

    public function test_show_hides_integrations_until_identity_verified(): void
    {
        $this->customer->linkExternalId('prestashop', '4242');

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->assertJsonPath('identity.verified', false)
            ->assertJsonPath('integrations', [])
            ->assertJsonPath('linkable_platforms', []);
    }

    // ─── request ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_request_identity_code(): void
    {
        $this->postJson(route('manager.helpdesk.customers.identity.request', $this->customer), ['channel' => 'email'])
            ->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_request_identity_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.customers.identity.request', $this->customer), ['channel' => 'email'])
            ->assertForbidden();
    }

    public function test_manager_can_request_email_code(): void
    {
        Mail::fake();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.request', $this->customer), ['channel' => 'email'])
            ->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertQueued(CustomerIdentityCodeMail::class);
        $this->assertDatabaseHas('helpdesk_customer_identity_verifications', [
            'customer_id' => $this->customer->id,
            'channel' => 'email',
        ], 'helpdesk');
    }

    /**
     * El asunto/cuerpo del correo se resuelven desde la plantilla del modulo
     * Mailer (helpdesk_integration.customer_identity_code), no desde una
     * vista Blade propia — ver HelpdeskIntegrationEmailTemplatesSeeder.
     */
    public function test_email_code_renders_from_mailer_template(): void
    {
        // La traduccion debe quedar atada al MISMO lang_id que resolvera
        // MailerLang::resolveDefaultId() en tiempo de envio — se captura antes
        // de crear nada para no depender de que exista (o no) un Lang default
        // ya sembrado en esta base de datos de test.
        $langId = Locale::resolveLegacyLangId();

        if (! Lang::query()->whereKey($langId)->exists()) {
            // forceCreate(): 'id' no esta en $fillable, un create() normal lo
            // descartaria silenciosamente y crearia una fila con OTRO id.
            Lang::query()->forceCreate([
                'id' => $langId,
                'uid' => (string) Str::uuid(),
                'title' => 'Español (test)',
                'iso_code' => 'es-test-'.Str::random(6),
                'lenguage_code' => 'es-test-'.Str::random(6),
                'available' => true,
            ]);
        }

        $template = MailerTemplate::query()->create([
            'uid' => (string) Str::uuid(),
            'key' => 'helpdesk_integration.customer_identity_code',
            'name' => 'Codigo de verificacion de identidad',
            'module' => 'helpdeskintegration',
            'is_enabled' => true,
        ]);

        MailerTemplateLang::query()->create([
            'uid' => (string) Str::uuid(),
            'mailer_template_id' => $template->id,
            'lang_id' => $langId,
            'subject' => 'Codigo para {CUSTOMER_NAME}',
            'content' => '<p>Hola {CUSTOMER_NAME}, tu codigo es {CODE} — {COMPANY_NAME}</p>',
        ]);

        Mail::fake();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.request', $this->customer), ['channel' => 'email'])
            ->assertOk();

        Mail::assertQueued(CustomerIdentityCodeMail::class, function (CustomerIdentityCodeMail $mail) {
            return str_contains($mail->emailSubject, $this->customer->name)
                && str_contains($mail->emailContent, $this->customer->name)
                && str_contains($mail->emailContent, config('app.name'))
                && preg_match('/tu codigo es \d{6}/', $mail->emailContent) === 1;
        });
    }

    public function test_sms_channel_rejected_when_flag_disabled(): void
    {
        Setting::set('integration.identity_sms_enabled', '0', 'integration');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.request', $this->customer), ['channel' => 'sms'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('channel');
    }

    public function test_sms_channel_allowed_when_flag_enabled(): void
    {
        Queue::fake();
        Setting::set('integration.identity_sms_enabled', '1', 'integration');

        $customer = Customer::factory()->create(['whatsapp_phone' => '+34600000000', 'email' => null]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.request', $customer), ['channel' => 'sms'])
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_customer_identity_verifications', [
            'customer_id' => $customer->id,
            'channel' => 'sms',
        ], 'helpdesk');
    }

    public function test_sms_delivery_is_queued_not_sent_synchronously(): void
    {
        Queue::fake();
        Setting::set('integration.identity_sms_enabled', '1', 'integration');

        $customer = Customer::factory()->create(['whatsapp_phone' => '+34600000000', 'email' => null]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.request', $customer), ['channel' => 'sms'])
            ->assertOk();

        Queue::assertPushedOn('notifications', SendIdentityCodeSmsJob::class);
    }

    public function test_request_rejects_email_channel_when_customer_has_no_email(): void
    {
        $customer = Customer::factory()->create(['email' => null]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.request', $customer), ['channel' => 'email'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    // ─── verify ───────────────────────────────────────────────────────────────

    public function test_manager_can_verify_correct_code(): void
    {
        $service = app(CustomerIdentityVerificationService::class);
        $service->requestCode($this->customer, 'email');

        $verification = CustomerIdentityVerification::query()->latest('id')->first();
        $code = $this->extractCodeFromHash($verification);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => $code])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('identity.verified', true)
            ->assertJsonStructure(['integrations', 'linkable_platforms']);
    }

    public function test_requesting_a_new_code_invalidates_previous_pending_codes(): void
    {
        $service = app(CustomerIdentityVerificationService::class);

        $service->requestCode($this->customer, 'email');
        $first = CustomerIdentityVerification::query()->latest('id')->first();
        $this->assertTrue($first->expires_at->isFuture());

        // Pedir otro código debe invalidar el anterior aún pendiente.
        $service->requestCode($this->customer, 'email');

        $this->assertTrue($first->fresh()->expires_at->isPast(), 'El código previo debe quedar expirado.');
        $this->assertSame(1, CustomerIdentityVerification::query()
            ->where('customer_id', $this->customer->id)
            ->whereNull('verified_at')
            ->where('expires_at', '>=', now())
            ->count(), 'Solo debe quedar un código válido a la vez.');
    }

    public function test_verify_rejects_incorrect_code(): void
    {
        app(CustomerIdentityVerificationService::class)->requestCode($this->customer, 'email');

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify', $this->customer), ['code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_show_exposes_integrations_after_verification(): void
    {
        $service = app(CustomerIdentityVerificationService::class);
        $service->requestCode($this->customer, 'email');
        $verification = CustomerIdentityVerification::query()->latest('id')->first();
        $service->confirmCode($this->customer, $this->extractCodeFromHash($verification));

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->assertJsonPath('identity.verified', true)
            ->assertJsonStructure(['integrations', 'linkable_platforms']);
    }

    /**
     * Decisión explícita (ago-2026): vincular ya NO exige identidad
     * verificada — antes esta prueba comprobaba justo lo contrario
     * (assertForbidden). sync()/unlink() sí la siguen exigiendo, ver
     * PlatformSyncTest y CustomerIntegrationsControllerTest::test_unlink_*.
     */
    public function test_link_no_longer_requires_identity_verified(): void
    {
        $this->seed(HelpdeskIntegrationProvidersSeeder::class);

        FakeIntegrationDriver::reset();
        app(IntegrationDriverRegistry::class)->register('prestashop', FakeIntegrationDriver::class);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.integrations.link', $this->customer), [
                'platform' => 'prestashop',
                'external_id' => '123',
            ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_customer_external_ids', [
            'customer_id' => $this->customer->id,
            'platform' => 'prestashop',
            'external_id' => '123',
        ], 'helpdesk');
    }

    // ─── verify manual ──────────────────────────────────────────────────────────

    public function test_manager_can_verify_identity_manually(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify-manual', $this->customer))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('identity.verified', true)
            ->assertJsonPath('identity.channel', 'manual');

        $this->assertDatabaseHas('helpdesk_customer_identity_verifications', [
            'customer_id' => $this->customer->id,
            'channel' => 'manual',
            'verified_by' => $this->manager->id,
        ], 'helpdesk');
    }

    public function test_guest_cannot_verify_identity_manually(): void
    {
        $this->postJson(route('manager.helpdesk.customers.identity.verify-manual', $this->customer))
            ->assertUnauthorized();
    }

    /**
     * Ver identidad de un cliente no basta para poder confirmarla sin
     * código: verify-manual desbloquea de inmediato los datos sensibles de
     * integraciones, así que exige el mismo permiso reforzado que
     * link()/unlink() (helpdesk.integrations.manage), no solo poder ver la
     * ficha del cliente.
     */
    public function test_user_who_can_view_customer_but_lacks_integrations_permission_cannot_verify_manually(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.customers.manage');

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.customers.identity.verify-manual', $this->customer))
            ->assertForbidden();
    }

    public function test_user_without_permission_cannot_verify_identity_manually(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('manager.helpdesk.customers.identity.verify-manual', $this->customer))
            ->assertForbidden();
    }

    public function test_manual_verification_shows_up_as_already_validated(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.customers.identity.verify-manual', $this->customer))
            ->assertOk();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.integrations.show', $this->customer))
            ->assertOk()
            ->assertJsonPath('identity.verified', true)
            ->assertJsonPath('identity.channel', 'manual');
    }

    /**
     * El codigo real no se expone fuera del servicio (solo su hash) — para
     * probar confirmCode() de forma determinista, sobreescribimos el hash
     * con uno conocido y devolvemos ese mismo codigo.
     */
    private function extractCodeFromHash(CustomerIdentityVerification $verification): string
    {
        $code = '123456';
        $verification->forceFill(['code_hash' => Hash::make($code)])->save();

        return $code;
    }
}
