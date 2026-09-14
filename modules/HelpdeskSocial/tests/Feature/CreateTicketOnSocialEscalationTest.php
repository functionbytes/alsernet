<?php

namespace Modules\HelpdeskSocial\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskSocial\Events\SocialCommentEscalated;
use Modules\HelpdeskSocial\Listeners\CreateTicketOnSocialEscalation;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Tests\TestCase;

/**
 * Se salta si HelpdeskSocial está deshabilitado (sus tablas no están migradas
 * en la BD de test); el TestCase base hace el markTestSkipped.
 */
class CreateTicketOnSocialEscalationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_escalation_creates_a_ticket_via_the_contract(): void
    {
        $conversation = Conversation::factory()->create();
        $account = SocialAccount::factory()->create(['platform' => 'facebook']);
        $comment = SocialComment::factory()->create([
            'social_account_id' => $account->id,
            'helpdesk_conversation_id' => $conversation->id,
            'status' => 'escalated',
            'body' => 'Esto es indignante, llevo semanas sin respuesta',
            'urgency' => 'critical',
        ]);

        $created = ['id' => 999, 'ticket_number' => 'TCK-2026-09999', 'url' => 'http://x'];
        $mock = $this->mock(TicketServiceContract::class);
        $mock->shouldReceive('createFromConversation')->once()->andReturn($created);

        (new CreateTicketOnSocialEscalation)->handle(
            new SocialCommentEscalated($comment)
        );

        $this->assertSame('TCK-2026-09999', $comment->fresh()->escalated_ticket_number);
    }

    public function test_escalation_is_idempotent(): void
    {
        $conversation = Conversation::factory()->create();
        $account = SocialAccount::factory()->create(['platform' => 'facebook']);
        $comment = SocialComment::factory()->create([
            'social_account_id' => $account->id,
            'helpdesk_conversation_id' => $conversation->id,
            'status' => 'escalated',
            'escalated_ticket_number' => 'TCK-2026-00001',
        ]);

        // Ya tiene ticket: el contrato NO debe invocarse.
        $mock = $this->mock(TicketServiceContract::class);
        $mock->shouldNotReceive('createFromConversation');

        (new CreateTicketOnSocialEscalation)->handle(
            new SocialCommentEscalated($comment)
        );

        $this->assertSame('TCK-2026-00001', $comment->fresh()->escalated_ticket_number);
    }
}
