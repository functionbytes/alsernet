<?php

namespace Modules\HelpdeskCompliance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Compliance\GdprDeletionService;
use Modules\HelpdeskCompliance\Jobs\ProcessComplianceCascadeJob;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Hallazgo de auditoría: la cascada GDPR no alcanzaba los expedientes de
 * HelpdeskDocument (documents), que se vinculan al cliente por
 * customer_email / customer_cellphone_normalized y contienen adjuntos KYC en
 * media-library. Cubre el DocumentComplianceHandler y el transporte de
 * email/teléfono en el evento (capturados ANTES del borrado core).
 */
class DocumentComplianceCascadeTest extends TestCase
{
    use DatabaseTransactions;

    /** Los documentos/media viven en la conexión por defecto; el resto en 'helpdesk'. */
    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function makeDocumentWithMedia(array $attributes): Document
    {
        $document = Document::create(array_merge([
            'customer_firstname' => 'Cliente',
            'customer_lastname' => 'De Prueba',
        ], $attributes));

        $document->addMedia(UploadedFile::fake()->create('dni_frontal.pdf', 50, 'application/pdf'))
            ->withCustomProperties(['document_type' => 'dni_frontal'])
            ->toMediaCollection('documents');

        return $document;
    }

    private function mediaCountFor(Document $document): int
    {
        return Media::query()
            ->where('model_type', $document->getMorphClass())
            ->where('model_id', $document->id)
            ->count();
    }

    // ─── el evento transporta las claves de match capturadas antes del borrado ─

    public function test_soft_deletion_event_carries_email_and_phones_captured_before_anonymization(): void
    {
        Event::fake([CustomerGdprDeleted::class]);

        $customer = Customer::factory()->create([
            'email' => 'gdpr-doc@example.test',
            'phone' => '+34 600 111 222',
            'whatsapp_phone' => '34600333444',
        ]);

        // El modelo normaliza los teléfonos al asignarlos: el evento debe
        // transportar los valores tal y como quedaron almacenados.
        $storedPhone = $customer->phone;
        $storedWhatsapp = $customer->whatsapp_phone;

        app(GdprDeletionService::class)->deleteCustomer($customer, false);

        // El soft delete anula email/phone en el Customer ANTES de despachar el
        // evento: las claves deben viajar en el propio evento.
        Event::assertDispatched(CustomerGdprDeleted::class, function (CustomerGdprDeleted $event) use ($storedPhone, $storedWhatsapp): bool {
            return $event->customerEmail === 'gdpr-doc@example.test'
                && in_array($storedPhone, $event->customerPhones, true)
                && in_array($storedWhatsapp, $event->customerPhones, true);
        });
    }

    public function test_hard_deletion_event_carries_email_even_though_customer_row_is_gone(): void
    {
        Event::fake([CustomerGdprDeleted::class]);

        $customer = Customer::factory()->create(['email' => 'gdpr-hard@example.test']);

        app(GdprDeletionService::class)->deleteCustomer($customer, true);

        Event::assertDispatched(
            CustomerGdprDeleted::class,
            fn (CustomerGdprDeleted $event): bool => $event->customerEmail === 'gdpr-hard@example.test'
        );
    }

    // ─── hard delete: borra expedientes y media ───────────────────────────────

    public function test_job_hard_mode_deletes_documents_and_media_matched_by_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'kyc-email@example.test']);
        $document = $this->makeDocumentWithMedia(['customer_email' => 'kyc-email@example.test']);

        $this->assertSame(1, $this->mediaCountFor($document));

        (new ProcessComplianceCascadeJob(
            $customer->id, true, [], ['deleted' => 1, 'anonymized' => 0], null,
            'kyc-email@example.test', []
        ))->handle();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        $this->assertSame(0, $this->mediaCountFor($document));
    }

    public function test_job_hard_mode_deletes_documents_matched_by_phone(): void
    {
        $customer = Customer::factory()->create(['email' => null, 'whatsapp_phone' => '34600111222']);

        // Mismo número con formato distinto: el match usa los últimos 9 dígitos
        // (customer_cellphone_normalized, mantenida por el modelo Document).
        $document = $this->makeDocumentWithMedia([
            'customer_email' => null,
            'customer_cellphone' => '600-111-222',
        ]);

        (new ProcessComplianceCascadeJob(
            $customer->id, true, [], ['deleted' => 1, 'anonymized' => 0], null,
            null, ['34600111222']
        ))->handle();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    // ─── soft delete: redacta PII y borra los adjuntos ────────────────────────

    public function test_job_soft_mode_redacts_document_pii_and_deletes_media(): void
    {
        $customer = Customer::factory()->create(['email' => 'kyc-soft@example.test']);
        $document = $this->makeDocumentWithMedia([
            'customer_email' => 'kyc-soft@example.test',
            'customer_cellphone' => '600111222',
            'customer_dni' => '12345678Z',
        ]);

        (new ProcessComplianceCascadeJob(
            $customer->id, false, [], ['deleted' => 0, 'anonymized' => 1], null,
            'kyc-soft@example.test', []
        ))->handle();

        $fresh = Document::query()->find($document->id);

        $this->assertNotNull($fresh, 'En modo soft el expediente se conserva (redactado), no se borra.');
        $this->assertNull($fresh->customer_email);
        $this->assertNull($fresh->customer_cellphone);
        $this->assertNull($fresh->customer_cellphone_normalized);
        $this->assertNull($fresh->customer_dni);
        $this->assertNull($fresh->customer_firstname);
        $this->assertNull($fresh->customer_lastname);
        $this->assertSame(0, $this->mediaCountFor($document), 'Los adjuntos KYC son PII y deben borrarse también en soft.');
    }

    // ─── clientes sin documentos y audit trail ────────────────────────────────

    public function test_job_completes_and_records_skipped_summary_for_customers_without_documents(): void
    {
        $customer = Customer::factory()->create();

        (new ProcessComplianceCascadeJob(
            $customer->id, false, [], ['deleted' => 0, 'anonymized' => 1], null,
            'sin-documentos@example.test', []
        ))->handle();

        $request = ComplianceRequest::query()->where('customer_id', $customer->id)->first();

        $this->assertNotNull($request);
        $this->assertSame('completed', $request->status);

        $documentSummary = collect($request->result_summary['handlers'] ?? [])
            ->firstWhere('module', 'HelpdeskDocument');

        $this->assertNotNull($documentSummary, 'El audit trail debe registrar el handler de documentos.');
        $this->assertSame(0, $documentSummary['documents']);
    }

    public function test_job_records_document_deletion_counts_in_the_audit_trail(): void
    {
        $customer = Customer::factory()->create(['email' => 'kyc-audit@example.test']);
        $this->makeDocumentWithMedia(['customer_email' => 'kyc-audit@example.test']);

        (new ProcessComplianceCascadeJob(
            $customer->id, true, [], ['deleted' => 1, 'anonymized' => 0], null,
            'kyc-audit@example.test', []
        ))->handle();

        $request = ComplianceRequest::query()->where('customer_id', $customer->id)->first();

        $this->assertNotNull($request);
        $this->assertContains('HelpdeskDocument', $request->modules_affected);

        $documentSummary = collect($request->result_summary['handlers'] ?? [])
            ->firstWhere('module', 'HelpdeskDocument');

        $this->assertSame(1, $documentSummary['documents']);
        $this->assertSame(1, $documentSummary['media_files']);
        $this->assertSame('deleted', $documentSummary['mode']);

        $this->assertDatabaseHas('helpdesk_audit_logs', [
            'action' => 'gdpr.cascade.completed',
        ], 'helpdesk');
    }
}
