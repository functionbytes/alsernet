<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskDocument\Services\ConversationDocumentLinker;

/**
 * Covers ConversationDocumentLinker — the service that lazily links a
 * Helpdesk conversation to its customer's Document expediente (matched by
 * email) and persists the link on conversation.metadata.
 */
class ConversationDocumentLinkerTest extends HelpdeskTestCase
{
    private ConversationDocumentLinker $linker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->linker = new ConversationDocumentLinker;
    }

    private function makeDocument(string $email, string $statusKey, ?\DateTimeInterface $createdAt = null): Document
    {
        $status = DocumentStatus::firstOrCreate(
            ['key' => $statusKey],
            ['label' => ucfirst($statusKey), 'color' => '#000000', 'is_active' => true, 'order' => 1]
        );

        $document = Document::create([
            'customer_email' => $email,
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Cliente',
            'status_id' => $status->id,
        ]);

        if ($createdAt) {
            $document->forceFill(['created_at' => $createdAt])->save();
        }

        return $document;
    }

    public function test_resolve_and_link_returns_existing_link_without_relinking(): void
    {
        $customer = Customer::factory()->create(['email' => 'cliente@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $conversation->forceFill(['metadata' => ['document_id' => 999]])->save();

        $result = $this->linker->resolveAndLink($conversation);

        $this->assertSame(999, $result);
        $this->assertArrayNotHasKey('document_linked_at', $conversation->fresh()->metadata ?? []);
    }

    public function test_resolve_and_link_links_best_candidate_and_persists_metadata(): void
    {
        $customer = Customer::factory()->create(['email' => 'cliente@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $document = $this->makeDocument('cliente@example.com', 'received');

        $result = $this->linker->resolveAndLink($conversation);

        $this->assertSame($document->id, $result);
        $this->assertSame($document->id, $conversation->fresh()->metadata['document_id']);
    }

    public function test_resolve_and_link_returns_null_when_customer_has_no_documents(): void
    {
        $customer = Customer::factory()->create(['email' => 'sin-documentos@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $result = $this->linker->resolveAndLink($conversation);

        $this->assertNull($result);
        $this->assertArrayNotHasKey('document_id', $conversation->fresh()->metadata ?? []);
    }

    public function test_documents_for_conversation_orders_by_status_priority_over_recency(): void
    {
        $email = 'priority@example.com';
        $customer = Customer::factory()->create(['email' => $email]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        // 'pending' is lower priority than 'received' but created more recently.
        $pending = $this->makeDocument($email, 'pending', now()->subDay());
        $received = $this->makeDocument($email, 'received', now()->subWeek());

        $ordered = $this->linker->documentsForConversation($conversation);

        $this->assertSame($received->id, $ordered->first()->id);
        $this->assertSame($pending->id, $ordered->last()->id);
    }

    public function test_documents_for_conversation_orders_by_recency_within_same_status(): void
    {
        $email = 'recency@example.com';
        $customer = Customer::factory()->create(['email' => $email]);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $older = $this->makeDocument($email, 'pending', now()->subWeek());
        $newer = $this->makeDocument($email, 'pending', now()->subDay());

        $ordered = $this->linker->documentsForConversation($conversation);

        $this->assertSame($newer->id, $ordered->first()->id);
        $this->assertSame($older->id, $ordered->last()->id);
    }

    public function test_documents_for_conversation_returns_empty_when_customer_has_no_email(): void
    {
        $customer = Customer::factory()->create(['email' => '']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->assertTrue($this->linker->documentsForConversation($conversation)->isEmpty());
    }

    public function test_documents_for_conversation_returns_empty_when_email_has_no_documents(): void
    {
        $customer = Customer::factory()->create(['email' => 'nadie-tiene-documentos@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->assertTrue($this->linker->documentsForConversation($conversation)->isEmpty());
    }

    /**
     * El match por email dejó de usar LOWER() (que anulaba el índice); la columna
     * es utf8mb4_unicode_ci, así que la igualdad ya es case-insensitive. Este test
     * fija ese comportamiento para que nadie reintroduzca la distinción de caso.
     */
    public function test_documents_for_conversation_matches_email_case_insensitively(): void
    {
        $customer = Customer::factory()->create(['email' => 'mixedcase@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $document = $this->makeDocument('MixedCase@Example.COM', 'received');

        $found = $this->linker->documentsForConversation($conversation);

        $this->assertTrue($found->contains('id', $document->id), 'El email debe casar sin distinguir mayúsculas/minúsculas.');
    }

    public function test_link_persists_metadata_fields_and_preserves_existing_keys(): void
    {
        $customer = Customer::factory()->create(['email' => 'cliente@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $conversation->forceFill(['metadata' => ['chat_document_imports' => [['files' => ['/x.jpg']]]]])->save();

        $document = $this->makeDocument('cliente@example.com', 'received');

        $this->linker->link($conversation, $document);

        $metadata = $conversation->fresh()->metadata;

        $this->assertSame($document->id, $metadata['document_id']);
        $this->assertSame((string) $document->uid, $metadata['document_uid']);
        $this->assertSame($document->status_id, $metadata['document_status_id']);
        $this->assertSame('received', $metadata['document_status_key']);
        $this->assertSame('auto:email', $metadata['document_link_source']);
        $this->assertArrayHasKey('document_linked_at', $metadata);
        $this->assertSame([['files' => ['/x.jpg']]], $metadata['chat_document_imports']);
    }

    // ─── Fallback por teléfono (clientes sin email, p. ej. WhatsApp) ─────────

    public function test_documents_matched_by_phone_when_customer_has_no_email(): void
    {
        $customer = Customer::factory()->create(['email' => '', 'phone' => '+34 611 22 33 44']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $document = $this->makeDocument('otro-email@example.com', 'received');
        $document->update(['customer_cellphone' => '611223344']);

        $found = $this->linker->documentsForConversation($conversation);

        $this->assertCount(1, $found);
        $this->assertSame($document->id, $found->first()->id);
    }

    public function test_phone_fallback_is_ignored_when_customer_has_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'con-email@example.com', 'phone' => '+34 611 22 33 44']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        // Mismo teléfono pero email distinto: con email presente NO debe matchear.
        $document = $this->makeDocument('otro-email@example.com', 'received');
        $document->update(['customer_cellphone' => '611223344']);

        $this->assertTrue($this->linker->documentsForConversation($conversation)->isEmpty());
    }

    public function test_link_source_is_phone_when_customer_has_no_email(): void
    {
        $customer = Customer::factory()->create(['email' => '', 'phone' => '611223344']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $document = $this->makeDocument('otro@example.com', 'received');

        $this->linker->link($conversation, $document);

        $this->assertSame('auto:phone', $conversation->fresh()->metadata['document_link_source']);
    }

    // ─── syncLink: refresh de snapshot y re-vínculo ──────────────────────────

    public function test_sync_link_creates_link_when_missing(): void
    {
        $customer = Customer::factory()->create(['email' => 'sync@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $document = $this->makeDocument('sync@example.com', 'received');

        $this->linker->syncLink($conversation, $this->linker->documentsForConversation($conversation));

        $this->assertSame($document->id, $conversation->fresh()->metadata['document_id']);
    }

    public function test_sync_link_refreshes_stale_snapshot_preserving_link_origin(): void
    {
        $customer = Customer::factory()->create(['email' => 'stale@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $document = $this->makeDocument('stale@example.com', 'received');

        $this->linker->link($conversation, $document);
        $linkedAt = $conversation->fresh()->metadata['document_linked_at'];

        // El expediente cambia de estado tras el primer link.
        $approved = DocumentStatus::firstOrCreate(
            ['key' => 'approved'],
            ['label' => 'Aprobado', 'color' => '#000000', 'is_active' => true, 'order' => 2]
        );
        $document->update(['status_id' => $approved->id]);

        $this->linker->syncLink($conversation->fresh(), $this->linker->documentsForConversation($conversation));

        $metadata = $conversation->fresh()->metadata;
        $this->assertSame('approved', $metadata['document_status_key']);
        $this->assertSame($linkedAt, $metadata['document_linked_at']);
        $this->assertSame('auto:email', $metadata['document_link_source']);
    }

    public function test_sync_link_relinks_when_linked_document_no_longer_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'roto@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $conversation->forceFill(['metadata' => ['document_id' => 99999]])->save();

        $document = $this->makeDocument('roto@example.com', 'received');

        $this->linker->syncLink($conversation, $this->linker->documentsForConversation($conversation));

        $this->assertSame($document->id, $conversation->fresh()->metadata['document_id']);
    }
}
