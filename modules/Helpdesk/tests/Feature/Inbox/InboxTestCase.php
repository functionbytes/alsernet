<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class InboxTestCase extends TestCase
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
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-settings');
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function createConversation(array $attrs = []): Conversation
    {
        $conversation = Conversation::factory()->create($attrs);

        if ($conversation->status_id === null) {
            $conversation->status_id = $this->openStatus->id;
            $conversation->save();
        }

        return $conversation;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function createCustomer(array $attrs = []): Customer
    {
        return Customer::factory()->create($attrs);
    }

    protected function createMessage(Conversation $conversation, string $body): ConversationItem
    {
        return $conversation->items()->create([
            'user_id' => $this->manager->id,
            'type' => 'message',
            'body' => $body,
            'is_internal' => false,
        ]);
    }
}
