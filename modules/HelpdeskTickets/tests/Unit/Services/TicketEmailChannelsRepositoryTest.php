<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Setting;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Tests\TestCase;

class TicketEmailChannelsRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' es la conexión real de Setting/settings — ver el comentario en
    // FetchTicketEmailsJobTest::$connectionsToTransact para el detalle del
    // incidente real que esto evita.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketEmailChannelsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->repository = new TicketEmailChannelsRepository;

        // Setting::setEncrypted()/getDecrypted() cachean fuera del rollback
        // transaccional del test (mismo gotcha ya documentado del proyecto:
        // un Setting escrito en un test puede sobrevivir en Redis/DB real
        // aunque la fila se revierta) — se siembra vacío explícitamente en
        // vez de asumir que cada test arranca desde cero.
        $this->seedBlob(['imap' => ['connections' => []]]);
    }

    private function seedBlob(array $blob): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'incoming_email'],
            ['value' => json_encode($blob)]
        );

        Cache::forget('setting_incoming_email');
    }

    public function test_all_returns_empty_array_when_nothing_configured(): void
    {
        $this->assertSame([], $this->repository->all());
    }

    public function test_create_assigns_id_and_health_defaults(): void
    {
        $created = $this->repository->create([
            'name' => 'Soporte',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'soporte@example.com',
            'password' => 'secret',
            'create_tickets' => true,
            'create_replies' => false,
        ]);

        $this->assertNotEmpty($created['id']);
        $this->assertArrayHasKey('last_checked_at', $created);
        $this->assertNull($created['last_checked_at']);
        $this->assertNull($created['last_error']);

        $all = $this->repository->all();
        $this->assertCount(1, $all);
        $this->assertSame('Soporte', $all[0]['name']);
    }

    public function test_create_preserves_other_incoming_email_keys(): void
    {
        // MailsSettings guarda pipe/api/gmail/mailgun/phplist en el mismo
        // blob — crear un canal no debe corromper esas claves.
        $this->seedBlob([
            'imap' => ['connections' => []],
            'pipe' => ['enabled' => true, 'mail_address' => 'pipe@example.com'],
            'api' => ['enabled' => true, 'api_key' => 'abc123'],
        ]);

        $this->repository->create([
            'name' => 'Canal nuevo',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'user@example.com',
            'password' => 'secret',
        ]);

        // setEncrypted() guarda el blob cifrado — se relee con
        // getDecrypted(), no con una lectura cruda de la tabla.
        $raw = json_decode(Setting::getDecrypted('incoming_email', '{}'), true);

        $this->assertTrue($raw['pipe']['enabled']);
        $this->assertSame('pipe@example.com', $raw['pipe']['mail_address']);
        $this->assertSame('abc123', $raw['api']['api_key']);
        $this->assertCount(1, $raw['imap']['connections']);
    }

    public function test_update_merges_fields_and_keeps_password_when_blank(): void
    {
        $created = $this->repository->create([
            'name' => 'Original',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'user@example.com',
            'password' => 'original-secret',
        ]);

        $updated = $this->repository->update($created['id'], [
            'name' => 'Renombrado',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'user@example.com',
            'password' => '',
        ]);

        $this->assertSame('Renombrado', $updated['name']);
        $this->assertSame('original-secret', $updated['password']);
    }

    /**
     * El formulario de edicion manda la contrasena vacia, pero el middleware
     * global ConvertEmptyStringsToNull la convierte en null antes de llegar al
     * repositorio: si solo se compara con '' se acaba guardando null y se
     * borra la contrasena del canal.
     */
    public function test_update_keeps_password_when_null(): void
    {
        $created = $this->repository->create([
            'name' => 'Original',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'user@example.com',
            'password' => 'original-secret',
        ]);

        $updated = $this->repository->update($created['id'], [
            'name' => 'Renombrado',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'user@example.com',
            'password' => null,
        ]);

        $this->assertSame('Renombrado', $updated['name']);
        $this->assertSame('original-secret', $updated['password']);
    }

    public function test_update_replaces_password_when_a_new_one_is_provided(): void
    {
        $created = $this->repository->create([
            'name' => 'Original',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'user@example.com',
            'password' => 'original-secret',
        ]);

        $updated = $this->repository->update($created['id'], [
            'password' => 'nueva-secret',
        ]);

        $this->assertSame('nueva-secret', $updated['password']);
    }

    public function test_update_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->repository->update('does-not-exist', ['name' => 'x']));
    }

    public function test_delete_removes_only_the_targeted_connection(): void
    {
        $a = $this->repository->create(['name' => 'A', 'host' => 'a.example.com', 'port' => 993, 'username' => 'a', 'password' => 'x']);
        $b = $this->repository->create(['name' => 'B', 'host' => 'b.example.com', 'port' => 993, 'username' => 'b', 'password' => 'x']);

        $this->repository->delete($a['id']);

        $remaining = $this->repository->all();
        $this->assertCount(1, $remaining);
        $this->assertSame($b['id'], $remaining[0]['id']);
    }

    public function test_record_health_success_sets_checked_and_success_timestamps(): void
    {
        $created = $this->repository->create(['name' => 'A', 'host' => 'a.example.com', 'port' => 993, 'username' => 'a', 'password' => 'x']);

        $this->repository->recordHealth($created['id'], success: true);

        $refreshed = $this->repository->find($created['id']);
        $this->assertNotNull($refreshed['last_checked_at']);
        $this->assertNotNull($refreshed['last_success_at']);
        $this->assertNull($refreshed['last_error']);
    }

    public function test_record_health_failure_sets_error_without_success_timestamp(): void
    {
        $created = $this->repository->create(['name' => 'A', 'host' => 'a.example.com', 'port' => 993, 'username' => 'a', 'password' => 'x']);

        $this->repository->recordHealth($created['id'], success: false, error: 'Connection refused');

        $refreshed = $this->repository->find($created['id']);
        $this->assertNotNull($refreshed['last_checked_at']);
        $this->assertNull($refreshed['last_success_at']);
        $this->assertSame('Connection refused', $refreshed['last_error']);
    }

    public function test_record_health_is_noop_for_deleted_connection(): void
    {
        // No debe lanzar si el canal se borró entre el fetch y este write.
        $this->repository->recordHealth('does-not-exist', success: true);
        $this->assertSame([], $this->repository->all());
    }

    public function test_all_heals_connections_saved_without_an_id(): void
    {
        // Reproduce un dato real encontrado en dev: una conexión guardada
        // antes de que 'id' existiera (o corrupta) rompía toda la pantalla
        // porque editar/sincronizar/eliminar de cada fila dependen de él.
        $this->seedBlob([
            'imap' => ['connections' => [
                ['name' => 'legacy', 'host' => 'imap.example.com', 'port' => 993, 'username' => 'u', 'password' => 'p'],
            ]],
        ]);

        $healed = $this->repository->all();

        $this->assertNotEmpty($healed[0]['id']);

        // Y queda persistido, no solo calculado al vuelo en esta llamada.
        $again = $this->repository->all();
        $this->assertSame($healed[0]['id'], $again[0]['id']);
    }

    public function test_default_returns_null_when_no_channel_is_marked(): void
    {
        $this->repository->create(['name' => 'A', 'host' => 'h', 'port' => 993, 'username' => 'a@example.com', 'password' => 'p']);

        $this->assertNull($this->repository->default());
    }

    public function test_set_default_marks_one_channel_and_unmarks_the_rest(): void
    {
        $a = $this->repository->create(['name' => 'A', 'host' => 'h', 'port' => 993, 'username' => 'a@example.com', 'password' => 'p', 'is_default' => true]);
        $b = $this->repository->create(['name' => 'B', 'host' => 'h', 'port' => 993, 'username' => 'b@example.com', 'password' => 'p']);

        $this->repository->setDefault($a['id']);
        $this->assertSame($a['id'], $this->repository->default()['id']);

        // Pasar el por defecto a B debe desmarcar A -- solo puede haber uno.
        $this->repository->setDefault($b['id']);
        $default = $this->repository->default();
        $this->assertSame($b['id'], $default['id']);
        $this->assertFalse($this->repository->find($a['id'])['is_default']);
    }
}
