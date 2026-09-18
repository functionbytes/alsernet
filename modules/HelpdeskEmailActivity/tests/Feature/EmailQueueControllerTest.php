<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailActivity\Database\Seeders\HelpdeskEmailActivityPermissionsSeeder;
use Tests\TestCase;

/**
 * Jobs fallidos de la cola de correo (EmailQueueController).
 *
 * Lo que de verdad importa probar aquí es el ACOTADO a la cola: failed_jobs es
 * una tabla compartida por toda la aplicación (en este entorno, ~15.000 filas
 * de las que solo 100 son de correo). Un controlador que se colara de cola
 * podría reintentar o purgar el trabajo de cualquier otro módulo, así que cada
 * test comprueba que el job ajeno sigue intacto.
 */
class EmailQueueControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailActivityPermissionsSeeder::class);
    }

    private function manager(): User
    {
        return tap(User::factory()->create())->givePermissionTo([
            'helpdeskemailactivity.view',
            'helpdeskemailactivity.manage',
        ]);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemailactivity.view');
    }

    private function failedJob(string $queue, string $displayName = 'Fixture\\Mail\\SomeMailable'): string
    {
        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'redis',
            'queue' => $queue,
            'payload' => json_encode(['displayName' => $displayName, 'uuid' => $uuid]),
            'exception' => "RuntimeException: fixture\n#0 stack",
            'failed_at' => Carbon::now(),
        ]);

        return $uuid;
    }

    public function test_index_only_lists_jobs_from_the_mail_queue(): void
    {
        $mine = $this->failedJob('emails', 'Fixture\\Mail\\MineMailable');
        $this->failedJob('helpdesk-erp-warming', 'Fixture\\Jobs\\NotMine');

        $response = $this->actingAs($this->manager())
            ->getJson(route('helpdeskemailactivity.queue.index'))
            ->assertOk();

        $uuids = array_column($response->json('jobs'), 'uuid');

        $this->assertContains($mine, $uuids);
        $this->assertSame(
            [],
            array_filter($response->json('jobs'), fn (array $job) => $job['name'] === 'NotMine'),
            'El modal no debe listar jobs de otras colas.',
        );
    }

    public function test_retrying_a_job_from_another_queue_is_rejected(): void
    {
        $foreign = $this->failedJob('helpdesk-erp-warming');

        $this->actingAs($this->manager())
            ->postJson(route('helpdeskemailactivity.queue.retry', ['uuid' => $foreign]))
            ->assertNotFound();

        // Y sigue donde estaba: no se ha tocado.
        $this->assertDatabaseHas('failed_jobs', ['uuid' => $foreign], 'mysql');
    }

    public function test_flush_only_deletes_jobs_from_the_mail_queue(): void
    {
        $mine = $this->failedJob('emails');
        $foreign = $this->failedJob('helpdesk-erp-warming');

        $this->actingAs($this->manager())
            ->deleteJson(route('helpdeskemailactivity.queue.flush'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $mine], 'mysql');
        $this->assertDatabaseHas('failed_jobs', ['uuid' => $foreign], 'mysql');
    }

    public function test_a_viewer_cannot_retry_or_flush(): void
    {
        $uuid = $this->failedJob('emails');
        $viewer = $this->viewer();

        $this->actingAs($viewer)
            ->postJson(route('helpdeskemailactivity.queue.retry', ['uuid' => $uuid]))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->deleteJson(route('helpdeskemailactivity.queue.flush'))
            ->assertForbidden();

        $this->assertDatabaseHas('failed_jobs', ['uuid' => $uuid], 'mysql');
    }

    public function test_a_viewer_can_still_see_the_queue_state(): void
    {
        // Consultar es solo lectura: no hace falta permiso de gestión.
        $this->actingAs($this->viewer())
            ->getJson(route('helpdeskemailactivity.queue.index'))
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
