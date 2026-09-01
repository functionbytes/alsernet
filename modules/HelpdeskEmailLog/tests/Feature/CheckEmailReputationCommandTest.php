<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Notifications\ReputationThresholdBreached;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CheckEmailReputationCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        // Reset del debounce entre tests — el comando persiste el estado
        // "roto"/"recuperado" en Setting entre corridas a propósito.
        Setting::set('helpdeskemaillog.reputation_breached_bounce_rate', '0');
        Setting::set('helpdeskemaillog.reputation_breached_complaint_rate', '0');
    }

    public function test_notifies_admins_when_bounce_rate_crosses_the_critical_threshold(): void
    {
        Notification::fake();

        // Umbral 0: la tasa de rebote (siempre >= 0) lo cruza por definición
        // — se prueba el MECANISMO de notificación (dispara al cruzar), no
        // el cálculo exacto de la tasa (eso ya lo cubre
        // EmailReputationControllerTest, acotado por dominio). Un umbral
        // realista tipo "1%" sería frágil aquí: el comando agrega TODO
        // email_logs sin acotar por dominio, y este entorno compartido tiene
        // contaminación real de otras corridas que podría diluir cualquier
        // señal fija que se intente crear.
        Setting::set('helpdeskemaillog.bounce_rate_critical_pct', '0');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->artisan('email-logs:check-reputation')->assertSuccessful();

        Notification::assertSentTo($admin, ReputationThresholdBreached::class, fn ($n) => $n->metric === 'bounce_rate');
    }

    public function test_does_not_notify_again_while_still_breached(): void
    {
        Notification::fake();

        Setting::set('helpdeskemaillog.bounce_rate_critical_pct', '0');
        EmailLog::factory()->create(['status' => EmailStatus::Bounced, 'sent_at' => now()]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->artisan('email-logs:check-reputation');
        $this->artisan('email-logs:check-reputation');

        Notification::assertSentToTimes($admin, ReputationThresholdBreached::class, 1);
    }

    public function test_notifies_again_after_recovering_and_breaching_once_more(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Corrida 1: umbral bajísimo, con datos reales rompe (asumiendo que
        // el entorno tiene AL MENOS algún envío histórico con resultado
        // terminal — si el entorno estuviera completamente vacío, 0% nunca
        // cruza un umbral de 0, así que se crea un rebote propio para
        // garantizarlo).
        EmailLog::factory()->create(['status' => EmailStatus::Bounced, 'sent_at' => now()]);
        Setting::set('helpdeskemaillog.bounce_rate_critical_pct', '0');
        $this->artisan('email-logs:check-reputation');

        // Corrida 2: umbral altísimo, "se recupera" (deja de estar roto).
        Setting::set('helpdeskemaillog.bounce_rate_critical_pct', '100');
        $this->artisan('email-logs:check-reputation');

        // Corrida 3: umbral bajo de nuevo, vuelve a romper.
        Setting::set('helpdeskemaillog.bounce_rate_critical_pct', '0');
        $this->artisan('email-logs:check-reputation');

        Notification::assertSentToTimes($admin, ReputationThresholdBreached::class, 2);
    }

    public function test_does_not_notify_when_below_threshold(): void
    {
        Notification::fake();

        Setting::set('helpdeskemaillog.bounce_rate_critical_pct', '100');
        Setting::set('helpdeskemaillog.complaint_rate_critical_pct', '100');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->artisan('email-logs:check-reputation')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
