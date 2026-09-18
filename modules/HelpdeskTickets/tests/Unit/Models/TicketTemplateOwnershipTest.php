<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketTemplate;
use Tests\TestCase;

class TicketTemplateOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_general_template_has_no_owner(): void
    {
        $template = TicketTemplate::factory()->create();

        $this->assertTrue($template->isGeneral());
        $this->assertNull($template->created_by);
    }

    public function test_owned_template_is_not_general(): void
    {
        $template = TicketTemplate::factory()->ownedBy(999)->create();

        $this->assertFalse($template->isGeneral());
        $this->assertSame(999, $template->created_by);
    }

    public function test_scope_general_only_returns_templates_without_owner(): void
    {
        TicketTemplate::factory()->create(['name' => 'general one']);
        TicketTemplate::factory()->ownedBy(1)->create(['name' => 'personal one']);

        $names = TicketTemplate::general()->pluck('name');

        $this->assertContains('general one', $names);
        $this->assertNotContains('personal one', $names);
    }

    public function test_scope_owned_by_only_returns_that_users_templates(): void
    {
        TicketTemplate::factory()->ownedBy(1)->create(['name' => 'user 1 template']);
        TicketTemplate::factory()->ownedBy(2)->create(['name' => 'user 2 template']);

        $names = TicketTemplate::ownedBy(1)->pluck('name');

        $this->assertContains('user 1 template', $names);
        $this->assertNotContains('user 2 template', $names);
    }

    public function test_scope_visible_to_returns_general_and_own_but_not_others(): void
    {
        TicketTemplate::factory()->create(['name' => 'shared']);
        TicketTemplate::factory()->ownedBy(1)->create(['name' => 'mine']);
        TicketTemplate::factory()->ownedBy(2)->create(['name' => 'someone elses']);

        $names = TicketTemplate::visibleTo(1)->pluck('name');

        $this->assertContains('shared', $names);
        $this->assertContains('mine', $names);
        $this->assertNotContains('someone elses', $names);
    }
}
