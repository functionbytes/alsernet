<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskEmailActivity\Database\Seeders\HelpdeskEmailActivityPermissionsSeeder;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Jobs\ResendEmailLogJob;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Tests\TestCase;

/**
 * Reenvío de TODO el resultado de un filtro (EmailLogController::bulkResendFiltered()).
 *
 * Es la acción del módulo con más consecuencias hacia fuera: manda correo real
 * a personas reales y no hay forma de retirarlo. Por eso los tests se centran
 * menos en el camino feliz y más en que las salvaguardas frenen de verdad —
 * cada una comprueba que NO se encoló ni un job.
 */
class BulkResendFilteredTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private const MODULE = 'FixtureBulkResendModule';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailActivityPermissionsSeeder::class);
        Queue::fake();
    }

    private function manager(): User
    {
        return tap(User::factory()->create())->givePermissionTo([
            'helpdeskemailactivity.view',
            'helpdeskemailactivity.manage',
        ]);
    }

    private function resendableLogs(int $count): void
    {
        EmailLog::factory()->count($count)->create([
            'module' => self::MODULE,
            'status' => EmailStatus::Failed,
            'to_addresses' => ['destino@ejemplo-test.invalid'],
            'body_html' => '<p>Contenido reenviable</p>',
        ]);
    }

    public function test_it_resends_every_row_matching_the_filter(): void
    {
        $this->resendableLogs(3);
        // Fuera del filtro: no debe tocarse.
        EmailLog::factory()->create(['module' => 'OtroModuloFixture']);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemailactivity.bulk-resend-filtered'), [
                'module' => self::MODULE,
                'expected' => 3,
            ])
            ->assertRedirect();

        Queue::assertPushed(ResendEmailLogJob::class, 3);
    }

    public function test_it_aborts_when_the_result_changed_since_the_screen_was_drawn(): void
    {
        // El usuario confirmó 2, pero ahora hay 3: alguien envió más correo
        // entre medias. Sin esta comprobación se reenviaría uno de más, sin
        // que nadie lo hubiera aprobado.
        $this->resendableLogs(3);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemailactivity.bulk-resend-filtered'), [
                'module' => self::MODULE,
                'expected' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    public function test_it_refuses_to_send_without_an_expected_count(): void
    {
        // Sin 'expected' no hay confirmación posible, así que no se envía nada
        // (el valor por defecto -1 nunca casa con un total real).
        $this->resendableLogs(2);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemailactivity.bulk-resend-filtered'), ['module' => self::MODULE])
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    public function test_it_skips_rows_that_cannot_be_resent(): void
    {
        $this->resendableLogs(2);
        // Sin destinatarios: no hay a quién reenviar.
        EmailLog::factory()->create([
            'module' => self::MODULE,
            'to_addresses' => [],
        ]);

        $this->actingAs($this->manager())
            ->post(route('helpdeskemailactivity.bulk-resend-filtered'), [
                'module' => self::MODULE,
                'expected' => 3,
            ])
            ->assertRedirect();

        Queue::assertPushed(ResendEmailLogJob::class, 2);
    }

    public function test_a_viewer_cannot_trigger_it(): void
    {
        $this->resendableLogs(2);
        $viewer = tap(User::factory()->create())->givePermissionTo('helpdeskemailactivity.view');

        $this->actingAs($viewer)
            ->post(route('helpdeskemailactivity.bulk-resend-filtered'), [
                'module' => self::MODULE,
                'expected' => 2,
            ])
            ->assertForbidden();

        Queue::assertNothingPushed();
    }
}
