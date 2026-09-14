<?php

namespace Modules\Helpdesk\Tests\Feature\Outbound;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Notifications\MetaTokenInvalidNotification;
use Modules\Helpdesk\Services\Channels\MetaTokenHealth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MetaTokenHealthTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        MetaTokenHealth::clear('whatsapp');
    }

    private function manager(): User
    {
        $role = Role::firstOrCreate(['name' => 'helpdesk-manager', 'guard_name' => 'web']);
        $manager = User::factory()->create();
        $manager->assignRole($role);

        return $manager;
    }

    public function test_notifica_a_managers_en_la_primera_deteccion(): void
    {
        Notification::fake();
        $manager = $this->manager();

        MetaTokenHealth::flagInvalid('whatsapp', ['code' => 190]);

        $this->assertTrue(MetaTokenHealth::isInvalid('whatsapp'));
        Notification::assertSentTo($manager, MetaTokenInvalidNotification::class);
    }

    public function test_no_re_notifica_en_detecciones_repetidas(): void
    {
        Notification::fake();
        $manager = $this->manager();

        MetaTokenHealth::flagInvalid('whatsapp', ['code' => 190]);
        MetaTokenHealth::flagInvalid('whatsapp', ['code' => 190]);

        Notification::assertSentToTimes($manager, MetaTokenInvalidNotification::class, 1);
    }

    public function test_statuses_refleja_el_estado_por_canal(): void
    {
        $statuses = MetaTokenHealth::statuses();
        $this->assertArrayHasKey('whatsapp', $statuses);
        $this->assertNull($statuses['whatsapp']);

        MetaTokenHealth::flagInvalid('whatsapp');

        $this->assertNotNull(MetaTokenHealth::statuses()['whatsapp']);
    }

    public function test_comando_de_estado_devuelve_fallo_si_hay_token_invalido(): void
    {
        $this->artisan('helpdesk:meta-token-status')->assertExitCode(0);

        MetaTokenHealth::flagInvalid('whatsapp');

        $this->artisan('helpdesk:meta-token-status')->assertExitCode(1);
    }
}
