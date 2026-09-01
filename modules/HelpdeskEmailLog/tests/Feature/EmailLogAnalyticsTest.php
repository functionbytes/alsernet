<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskEmailLog\Database\Seeders\HelpdeskEmailLogPermissionsSeeder;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Http\Controllers\EmailLogAnalyticsController;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailLogOpen;
use Modules\HelpdeskEmailLog\Services\EmailLogAnalyticsService;
use Tests\TestCase;

/**
 * Ancla temporal fija y lejana (año 2001) para todos los fixtures: la BD
 * comparte ~1.500 registros reales con created_at reciente (ver
 * EmailLogControllerTest para el mismo gotcha de 'mysql' en
 * connectionsToTransact) — acotar el rango [$from, $to] a esta ventana
 * histórica aísla las aserciones de esa contaminación sin depender de
 * dominios/mailables "de fixture" que igualmente podrían colisionar por
 * casualidad con datos reales.
 */
class EmailLogAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private EmailLogAnalyticsService $service;

    private Carbon $from;

    private Carbon $to;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailLogPermissionsSeeder::class);

        $this->service = app(EmailLogAnalyticsService::class);
        $this->from = Carbon::create(2001, 3, 1, 0, 0, 0);
        $this->to = Carbon::create(2001, 3, 31, 23, 59, 59);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemaillog.view');
    }

    // --- Rendimiento por mailable -------------------------------------------------

    public function test_open_rate_divides_by_tracked_sends_not_by_total_sends(): void
    {
        $createdAt = $this->from->copy()->addDays(5);

        // 2 envíos CON seguimiento: 1 con apertura registrada, 1 sin.
        $opened = EmailLog::factory()->tracked()->create([
            'mailable_class' => 'Fixture\\Mail\\OpenRateMailable',
            'status' => EmailStatus::Sent,
            'created_at' => $createdAt,
        ]);
        EmailLogOpen::query()->create([
            'email_log_id' => $opened->id,
            'opened_at' => $createdAt->copy()->addMinutes(10),
        ]);

        EmailLog::factory()->tracked()->create([
            'mailable_class' => 'Fixture\\Mail\\OpenRateMailable',
            'status' => EmailStatus::Sent,
            'created_at' => $createdAt,
        ]);

        // 1 envío SIN seguimiento (nunca llevó píxel) — si el rate se
        // calculara sobre el total (3) en vez de sobre los que sí tenían
        // seguimiento (2), esta fila haría que 50% (1/2) se convirtiera en
        // 33.3% (1/3), y el test fallaría.
        EmailLog::factory()->create([
            'mailable_class' => 'Fixture\\Mail\\OpenRateMailable',
            'status' => EmailStatus::Sent,
            'created_at' => $createdAt,
        ]);

        $row = $this->service->mailablePerformance($this->from, $this->to)
            ->firstWhere('mailable_class', 'Fixture\\Mail\\OpenRateMailable');

        $this->assertNotNull($row);
        $this->assertSame(3, $row['sends']);
        $this->assertSame(2, $row['open_tracked']);
        $this->assertSame(1, $row['opened']);
        $this->assertSame(50.0, $row['open_rate']);
    }

    public function test_delivery_and_bounce_rate_divide_by_total_sends(): void
    {
        $createdAt = $this->from->copy()->addDays(6);
        $mailable = 'Fixture\\Mail\\DeliveryRateMailable';

        EmailLog::factory()->count(3)->create([
            'mailable_class' => $mailable,
            'status' => EmailStatus::Sent,
            'created_at' => $createdAt,
        ]);
        EmailLog::factory()->create([
            'mailable_class' => $mailable,
            'status' => EmailStatus::Bounced,
            'created_at' => $createdAt,
        ]);
        EmailLog::factory()->queued()->create([
            'mailable_class' => $mailable,
            'created_at' => $createdAt,
        ]);

        $row = $this->service->mailablePerformance($this->from, $this->to)
            ->firstWhere('mailable_class', $mailable);

        $this->assertNotNull($row);
        // 5 envíos totales (incluye el 'queued' en el denominador — a
        // diferencia de EmailLog::reputationStats(), que lo excluye): 3
        // sent -> 60%, 1 bounced -> 20%.
        $this->assertSame(5, $row['sends']);
        $this->assertSame(60.0, $row['delivery_rate']);
        $this->assertSame(20.0, $row['bounce_rate']);
    }

    public function test_mailable_without_class_groups_under_null(): void
    {
        $createdAt = $this->from->copy()->addDays(7);

        EmailLog::factory()->withoutModule()->create([
            'status' => EmailStatus::Sent,
            'created_at' => $createdAt,
        ]);

        $row = $this->service->mailablePerformance($this->from, $this->to)
            ->firstWhere('mailable_class', null);

        $this->assertNotNull($row);
        $this->assertGreaterThanOrEqual(1, $row['sends']);
    }

    // --- Latencia de entrega -------------------------------------------------

    public function test_latency_percentiles_ignore_sends_without_delivered_at(): void
    {
        $createdAt = $this->from->copy()->addDays(10);

        // Diffs deterministas: 1,2,3,4,5,100 segundos -> p50 = 3.5 (media de
        // 3 y 4), p95 = 76.25 (interpolación continua) redondeado a 1
        // decimal = 76.3 (mismo round(..., 1) que el resto del módulo, ver
        // EmailLogController::rateFrom()), max = 100.
        foreach ([1, 2, 3, 4, 5, 100] as $seconds) {
            EmailLog::factory()->create([
                'created_at' => $createdAt,
                'sent_at' => $createdAt,
                'delivered_at' => $createdAt->copy()->addSeconds($seconds),
            ]);
        }

        // Nunca deben contar: mismo rango de fechas, pero sin delivered_at.
        EmailLog::factory()->count(4)->create([
            'created_at' => $createdAt,
            'sent_at' => $createdAt,
            'delivered_at' => null,
        ]);

        $latency = $this->service->deliveryLatency($this->from, $this->to);

        $this->assertSame(6, $latency['sample_size']);
        $this->assertSame(3.5, $latency['p50_seconds']);
        $this->assertSame(76.3, $latency['p95_seconds']);
        $this->assertSame(100, $latency['max_seconds']);
    }

    public function test_latency_is_an_honest_empty_state_when_nothing_has_delivered_at(): void
    {
        $emptyFrom = Carbon::create(2002, 1, 1);
        $emptyTo = Carbon::create(2002, 1, 31);

        EmailLog::factory()->create([
            'created_at' => $emptyFrom->copy()->addDay(),
            'sent_at' => $emptyFrom->copy()->addDay(),
            'delivered_at' => null,
        ]);

        $latency = $this->service->deliveryLatency($emptyFrom, $emptyTo);

        $this->assertSame(0, $latency['sample_size']);
        $this->assertNull($latency['p50_seconds']);
        $this->assertNull($latency['p95_seconds']);
        $this->assertNull($latency['max_seconds']);
    }

    // --- Entregabilidad por dominio destinatario -------------------------------------------------

    public function test_domain_grouping_uses_the_primary_recipient_with_multiple_recipients(): void
    {
        $createdAt = $this->from->copy()->addDays(15);

        // to_addresses[0] es "primary.fixture.test" — cc/bcc no deben
        // desplazar el dominio agrupado (recipients_index conserva el orden
        // to->cc->bcc, ver EmailLog::booting()).
        EmailLog::factory()->create([
            'status' => EmailStatus::Sent,
            'created_at' => $createdAt,
            'to_addresses' => ['user@primary.fixture.test'],
            'cc_addresses' => ['other@secondary.fixture.test'],
        ]);

        EmailLog::factory()->create([
            'status' => EmailStatus::Bounced,
            'created_at' => $createdAt,
            'to_addresses' => ['user2@primary.fixture.test'],
        ]);

        $result = $this->service->domainDeliverability($this->from, $this->to);

        $primary = $result['domains']->firstWhere('domain', 'primary.fixture.test');
        $secondary = $result['domains']->firstWhere('domain', 'secondary.fixture.test');

        $this->assertNotNull($primary);
        $this->assertSame(2, $primary['volume']);
        $this->assertSame(50.0, $primary['delivery_rate']);
        $this->assertSame(50.0, $primary['bounce_rate']);

        // El dominio de cc nunca debe recibir su propia fila: no se
        // "explota" el envío en un registro por destinatario.
        $this->assertNull($secondary);
    }

    public function test_domains_beyond_the_limit_are_aggregated_as_others(): void
    {
        $createdAt = $this->from->copy()->addDays(20);

        EmailLog::factory()->count(3)->create([
            'status' => EmailStatus::Sent,
            'created_at' => $createdAt,
            'to_addresses' => ['a@big-fixture.test'],
        ]);
        EmailLog::factory()->create([
            'status' => EmailStatus::Sent,
            'created_at' => $createdAt,
            'to_addresses' => ['b@small-fixture.test'],
        ]);

        $result = $this->service->domainDeliverability($this->from, $this->to, limit: 1);

        $this->assertCount(1, $result['domains']);
        $this->assertSame('big-fixture.test', $result['domains'][0]['domain']);

        $this->assertNotNull($result['others']);
        $this->assertNull($result['others']['domain']);
        $this->assertSame(1, $result['others']['volume']);
    }

    // --- Controlador (permiso + gating del módulo) -------------------------------------------------

    public function test_controller_requires_view_permission(): void
    {
        // La ruta real la registrará quien coordine este trabajo (ver
        // informe) — aquí se registra de forma efímera solo para esta
        // aserción, sin tocar routes/web.php.
        Route::middleware('web')->get('/__test/helpdeskemaillog/analytics', [EmailLogAnalyticsController::class, 'index']);

        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get('/__test/helpdeskemaillog/analytics')
            ->assertForbidden();
    }

    public function test_controller_renders_for_a_viewer(): void
    {
        Route::middleware('web')->get('/__test/helpdeskemaillog/analytics', [EmailLogAnalyticsController::class, 'index']);

        $this->actingAs($this->viewer())
            ->get('/__test/helpdeskemaillog/analytics')
            ->assertOk()
            ->assertViewIs('helpdeskemaillog::emails.analytics')
            ->assertViewHasAll(['from', 'to', 'mailables', 'latency', 'domains', 'thresholds']);
    }
}
