<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Http\Controllers\Settings\WebhookEventsController;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\ProviderWebhookEvent;
use Modules\HelpdeskEmailLog\Services\ProviderWebhookSettingsRepository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * routes/web.php todavía no registra estas dos rutas (fuera del alcance de
 * este cambio a propósito — otro agente las añade, ver informe). Se
 * declaran aquí ad hoc, con el MISMO nombre que tendrán en producción
 * (settings.helpdeskemaillog.webhook-events.*) y apuntando al controlador
 * real: el middleware `can:...` se resuelve desde el controlador
 * (constructor), no desde la ruta, y la propia vista genera enlaces con
 * route('settings.helpdeskemaillog.webhook-events.index') — así que este
 * test ya cubre el comportamiento real tal cual se comportará en cuanto la
 * ruta quede registrada de verdad en el módulo (momento en el que este
 * registro ad hoc se vuelve redundante/inofensivo y puede retirarse).
 */
class WebhookEventsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('helpdeskemaillog.settings.view', 'web');
        Permission::findOrCreate('helpdeskemaillog.settings.update', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['helpdeskemaillog.settings.view', 'helpdeskemaillog.settings.update']);

        if (! Route::has('settings.helpdeskemaillog.webhook-events.index')) {
            Route::middleware(['web', 'auth'])->group(function (): void {
                Route::get('/panel/settings/helpdeskemaillog/webhook-events', [WebhookEventsController::class, 'index'])
                    ->name('settings.helpdeskemaillog.webhook-events.index');
                Route::post('/panel/settings/helpdeskemaillog/webhook-events/{event}/reprocess', [WebhookEventsController::class, 'reprocess'])
                    ->name('settings.helpdeskemaillog.webhook-events.reprocess');
            });
        }

        // El array de nombres de ruta (nameList) que usan route()/Route::has()
        // se construye UNA vez al arrancar la app (ver App\Providers\
        // RouteServiceProvider -> loadRoutes()), no de forma incremental por
        // cada Route::get()/post() — sin este refresh, las dos rutas de
        // arriba quedarían registradas (cuentan en Route::getRoutes()) pero
        // invisibles para route(), con RouteNotFoundException.
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function configureProvider(string $provider, array $overrides = []): void
    {
        app(ProviderWebhookSettingsRepository::class)->save(array_merge([
            'provider' => $provider,
            'secret' => 'shared-secret',
            'process_bounces' => true,
            'process_complaints' => true,
        ], $overrides));
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('settings.helpdeskemaillog.webhook-events.index'))->assertRedirect();
    }

    public function test_index_requires_settings_view_permission(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('settings.helpdeskemaillog.webhook-events.index'))
            ->assertForbidden();
    }

    public function test_index_renders_health_panel_and_events_for_authorized_user(): void
    {
        $this->configureProvider('mailrelay');

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailrelay']), [
            'type' => 'hard_bounce', 'email' => 'x@example.test', 'id' => 'evt-idx-1',
        ], ['X-Mailrelay-Token' => 'shared-secret']);

        $this->actingAs($this->manager)
            ->get(route('settings.helpdeskemaillog.webhook-events.index'))
            ->assertOk()
            ->assertViewHas('health', function ($health) {
                $mailrelay = collect($health)->firstWhere('key', 'mailrelay');

                return $mailrelay['is_active'] === true
                    && $mailrelay['has_secret'] === true
                    && $mailrelay['today_count'] >= 1;
            });
    }

    public function test_reprocess_requires_settings_update_permission(): void
    {
        $event = $this->createUncorrelatedEvent();

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('helpdeskemaillog.settings.view');

        $this->actingAs($viewer)
            ->post(route('settings.helpdeskemaillog.webhook-events.reprocess', $event))
            ->assertForbidden();
    }

    public function test_reprocessing_an_event_that_now_correlates_updates_the_email_log(): void
    {
        // Al recibirlo, process_complaints estaba desactivado: se guarda sin
        // intentar correlacionar (processed_at null, email_log_id null).
        $this->configureProvider('mailrelay', ['process_complaints' => false]);
        $log = EmailLog::factory()->create(['message_id' => 'mailrelay-reproc-1@webadmin.test', 'status' => EmailStatus::Sent]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailrelay']), [
            'type' => 'complaint',
            'message_id' => 'mailrelay-reproc-1@webadmin.test',
            'email' => 'x@example.test',
            'id' => 'mailrelay-evt-reproc-1',
        ], ['X-Mailrelay-Token' => 'shared-secret'])->assertOk();

        $event = ProviderWebhookEvent::query()->where('provider', 'mailrelay')->where('provider_event_id', 'mailrelay-evt-reproc-1')->sole();
        $this->assertNull($event->email_log_id);
        $this->assertNull($event->processed_at);

        // El admin activa "procesar quejas" y reprocesa manualmente — sin
        // pedirle nada al proveedor, solo con el payload ya guardado.
        $this->configureProvider('mailrelay', ['process_complaints' => true]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemaillog.webhook-events.reprocess', $event))
            ->assertRedirect()
            ->assertSessionHas('success');

        $event->refresh();
        $this->assertSame($log->id, $event->email_log_id);
        $this->assertNotNull($event->processed_at);
        $this->assertSame(EmailStatus::Complained, $log->fresh()->status);
    }

    public function test_reprocessing_respects_the_disabled_toggle(): void
    {
        $this->configureProvider('mailrelay', ['process_complaints' => false]);
        $event = $this->createUncorrelatedEvent(eventType: 'complaint');

        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemaillog.webhook-events.reprocess', $event))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $event->refresh();
        $this->assertNull($event->email_log_id);
        $this->assertNull($event->processed_at);
    }

    public function test_reprocessing_an_already_correlated_event_is_refused(): void
    {
        $this->configureProvider('mailrelay');
        $log = EmailLog::factory()->create(['status' => EmailStatus::Sent]);

        $event = ProviderWebhookEvent::query()->create([
            'provider' => 'mailrelay',
            'provider_event_id' => 'evt-already-correlated-1',
            'event_type' => 'bounce',
            'payload' => $this->parsedPayload(recipient: 'x@example.test'),
            'email_log_id' => $log->id,
            'processed_at' => now(),
            'created_at' => now(),
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemaillog.webhook-events.reprocess', $event))
            ->assertRedirect()
            ->assertSessionHas('error');

        // No lo toca: sigue apuntando exactamente a donde ya apuntaba.
        $this->assertSame($log->id, $event->fresh()->email_log_id);
    }

    public function test_reprocessing_a_legacy_event_without_parsed_payload_is_refused(): void
    {
        $this->configureProvider('mailrelay');

        // Fila anterior a la migración de payload/event_type: ambos quedan null.
        $event = ProviderWebhookEvent::query()->create([
            'provider' => 'mailrelay',
            'provider_event_id' => 'evt-legacy-1',
            'created_at' => now(),
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemaillog.webhook-events.reprocess', $event))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    /**
     * @return array<string, mixed>
     */
    private function parsedPayload(?string $messageId = null, ?string $recipient = null): array
    {
        return [
            'raw' => ['email' => $recipient],
            'parsed' => [
                'message_id' => $messageId,
                'recipient' => $recipient,
                'is_hard' => true,
                'reason' => 'test',
                'ip' => null,
                'user_agent' => null,
            ],
        ];
    }

    private function createUncorrelatedEvent(string $eventType = 'bounce'): ProviderWebhookEvent
    {
        return ProviderWebhookEvent::query()->create([
            'provider' => 'mailrelay',
            'provider_event_id' => 'evt-uncorrelated-'.$eventType,
            'event_type' => $eventType,
            'payload' => $this->parsedPayload(recipient: 'nadie@example.test'),
            'created_at' => now(),
        ]);
    }
}
