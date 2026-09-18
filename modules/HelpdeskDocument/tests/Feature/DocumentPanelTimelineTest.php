<?php

namespace Modules\HelpdeskDocument\Tests\Feature;

use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentAction;
use Modules\Document\Entities\DocumentMail;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskDocument\Services\DocumentPanelPresenter;

/**
 * Timeline unificada del tab Historial (mockup Alvarez): acciones y correos
 * fusionados en una sola cronología descendente.
 */
class DocumentPanelTimelineTest extends HelpdeskTestCase
{
    private function makeDocumentWithActivity(): Document
    {
        $document = Document::create([
            'customer_email' => 'timeline@example.com',
            'customer_firstname' => 'Time',
            'customer_lastname' => 'Line',
        ]);

        $created = DocumentAction::create([
            'document_id' => $document->id,
            'action_type' => 'created',
            'action_name' => 'Expediente creado',
            'performed_by_type' => 'Sistema',
        ]);
        $created->created_at = now()->subHours(3);
        $created->save();

        DocumentMail::create([
            'document_id' => $document->id,
            'email_type' => 'request',
            'recipient_email' => 'timeline@example.com',
            'subject' => 'Documentación requerida',
            'body_html' => '<p>Documentación requerida</p>',
            'status' => 'sent',
            'sent_at' => now()->subHours(2),
        ]);

        $approved = DocumentAction::create([
            'document_id' => $document->id,
            'action_type' => 'stage_approved',
            'action_name' => 'Etapa aprobada',
            'performed_by_type' => 'María',
        ]);
        $approved->created_at = now()->subHour();
        $approved->save();

        return $document;
    }

    public function test_timeline_merges_actions_and_mails_in_descending_order(): void
    {
        $document = $this->makeDocumentWithActivity();

        $timeline = app(DocumentPanelPresenter::class)->present($document->fresh())['timeline'];

        $this->assertCount(3, $timeline);
        $this->assertSame(
            ['Etapa aprobada', 'Solicitud inicial', 'Expediente creado'],
            array_column($timeline, 'label')
        );
        $this->assertSame(['action', 'mail', 'action'], array_column($timeline, 'kind'));
    }

    public function test_timeline_marks_approvals_and_mails_with_distinct_dots(): void
    {
        $document = $this->makeDocumentWithActivity();

        $timeline = app(DocumentPanelPresenter::class)->present($document->fresh())['timeline'];

        $byLabel = collect($timeline)->keyBy('label');
        $this->assertSame('ok', $byLabel['Etapa aprobada']['dot']);
        $this->assertSame('mail', $byLabel['Solicitud inicial']['dot']);
        $this->assertSame('', $byLabel['Expediente creado']['dot']);
    }

    public function test_mail_timeline_entry_includes_subject_in_sub(): void
    {
        $document = $this->makeDocumentWithActivity();

        $timeline = app(DocumentPanelPresenter::class)->present($document->fresh())['timeline'];

        $mailEntry = collect($timeline)->firstWhere('kind', 'mail');
        $this->assertStringContainsString('Documentación requerida', $mailEntry['sub']);
    }

    public function test_panel_renders_unified_timeline(): void
    {
        $document = $this->makeDocumentWithActivity();

        $customer = Customer::factory()->create(['email' => 'timeline@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.documents.panel', [$conversation->id, $document->id]))
            ->assertOk()
            ->assertSee('Actividad del expediente')
            ->assertSee('Etapa aprobada')
            ->assertSee('docs-utl', false);
    }

    public function test_panel_renders_labelled_stepper_and_edit_action(): void
    {
        $document = $this->makeDocumentWithActivity();

        $customer = Customer::factory()->create(['email' => 'timeline@example.com']);
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.documents.panel', [$conversation->id, $document->id]))
            ->assertOk()
            // Stepper con etiquetas por etapa (mockup .wv-steps) y edición
            // inline de datos del cliente (sin modal aparte).
            ->assertSee('docs-wv-steps', false)
            ->assertSee('Editar datos')
            ->assertSee('docs-cust-edit-toggle', false)
            // Los modales muertos ya no se incluyen.
            ->assertDontSee('id="docsViewer_', false)
            ->assertDontSee('id="docView_', false)
            ->assertDontSee('id="docManage_', false);
    }
}
