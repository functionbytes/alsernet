<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Mail\ConversationExportMail;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Conversation transcript export (#57 ve-export): PDF / CSV / JSON / EML,
 * plus "send by email" (delivers to the requesting agent's own address).
 */
class ConversationExportTest extends HelpdeskTestCase
{
    private const ROUTE = 'manager.helpdesk.exports.conversation-transcript';

    private const EMAIL_ROUTE = 'manager.helpdesk.exports.conversation-transcript.email';

    public function test_exports_conversation_as_json(): void
    {
        $conversation = $this->createConversation();
        ConversationItem::factory()->create([
            'conversation_id' => $conversation->id, 'user_id' => null, 'body' => 'Hola',
        ]);

        $this->actingAs($this->manager)
            ->get(route(self::ROUTE, ['conversation_id' => $conversation->id, 'format' => 'json']))
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('conversation.id', $conversation->id);
    }

    public function test_exports_conversation_as_csv(): void
    {
        $conversation = $this->createConversation();
        ConversationItem::factory()->create(['conversation_id' => $conversation->id, 'body' => 'Mensaje CSV']);

        $response = $this->actingAs($this->manager)
            ->get(route(self::ROUTE, ['conversation_id' => $conversation->id, 'format' => 'csv']))
            ->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_exports_conversation_as_pdf(): void
    {
        $conversation = $this->createConversation();
        ConversationItem::factory()->create(['conversation_id' => $conversation->id]);

        $response = $this->actingAs($this->manager)
            ->get(route(self::ROUTE, ['conversation_id' => $conversation->id, 'format' => 'pdf']))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_excludes_internal_notes_unless_requested(): void
    {
        $conversation = $this->createConversation();
        ConversationItem::factory()->create([
            'conversation_id' => $conversation->id, 'is_internal' => true, 'body' => 'Nota secreta',
        ]);

        // Sin include_notes: la nota interna NO aparece.
        $this->actingAs($this->manager)
            ->get(route(self::ROUTE, ['conversation_id' => $conversation->id, 'format' => 'json']))
            ->assertOk()
            ->assertJsonMissing(['body' => 'Nota secreta']);

        // Con include_notes: sí aparece.
        $this->actingAs($this->manager)
            ->get(route(self::ROUTE, ['conversation_id' => $conversation->id, 'format' => 'json', 'include_notes' => 1]))
            ->assertOk()
            ->assertJsonFragment(['body' => 'Nota secreta']);
    }

    public function test_exports_conversation_as_eml(): void
    {
        $conversation = $this->createConversation();
        ConversationItem::factory()->create(['conversation_id' => $conversation->id, 'body' => 'Mensaje EML']);

        $response = $this->actingAs($this->manager)
            ->get(route(self::ROUTE, ['conversation_id' => $conversation->id, 'format' => 'eml']))
            ->assertOk();

        $this->assertStringContainsString('message/rfc822', $response->headers->get('content-type'));
        $this->assertStringContainsString('Mensaje EML', $response->getContent());
    }

    public function test_pdf_omits_customer_header_when_include_header_is_false(): void
    {
        $customer = Customer::factory()->create(['name' => 'Cliente Confidencial']);
        $conversation = $this->createConversation(['customer_id' => $customer->id]);
        ConversationItem::factory()->create(['conversation_id' => $conversation->id]);

        $withHeader = $this->actingAs($this->manager)
            ->get(route(self::ROUTE, ['conversation_id' => $conversation->id, 'format' => 'pdf', 'include_header' => 1]))
            ->assertOk();

        $withoutHeader = $this->actingAs($this->manager)
            ->get(route(self::ROUTE, ['conversation_id' => $conversation->id, 'format' => 'pdf', 'include_header' => 0]))
            ->assertOk();

        // El PDF binario no es buscable como texto plano; basta con que difieran
        // en tamaño (el bloque de encabezado deja de renderizarse).
        $this->assertNotSame(
            strlen($withHeader->getContent()),
            strlen($withoutHeader->getContent())
        );
    }

    public function test_send_by_email_delivers_to_the_requesting_agent(): void
    {
        Mail::fake();

        $conversation = $this->createConversation();
        ConversationItem::factory()->create(['conversation_id' => $conversation->id, 'body' => 'Hola']);

        $this->actingAs($this->manager)
            ->postJson(route(self::EMAIL_ROUTE), [
                'conversation_id' => $conversation->id,
                'format' => 'pdf',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // ConversationExportMail implements ShouldQueue, así que Mail::to()->send()
        // lo encola en vez de enviarlo síncronamente — assertQueued(), no assertSent().
        Mail::assertQueued(ConversationExportMail::class, function (ConversationExportMail $mail) use ($conversation) {
            return $mail->conversation->id === $conversation->id
                && $mail->hasTo($this->manager->email)
                && str_ends_with($mail->attachmentFilename, '.pdf');
        });
    }

    public function test_rejects_invalid_format(): void
    {
        $conversation = $this->createConversation();

        $this->actingAs($this->manager)
            ->getJson(route(self::ROUTE, ['conversation_id' => $conversation->id, 'format' => 'xml']))
            ->assertStatus(422);
    }

    private function createConversation(array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create(array_merge(['channel' => 'web'], $overrides));
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }
}
