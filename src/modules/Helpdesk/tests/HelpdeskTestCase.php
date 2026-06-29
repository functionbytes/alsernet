<?php

namespace Modules\Helpdesk\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\ConversationStatus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class HelpdeskTestCase extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected User $manager;

    protected ConversationStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->openStatus = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-settings');
    }
}
