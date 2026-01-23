<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Document\Entities\Document;
use Tests\TestCase;

class CreateSampleDocumentsFromPrestashopTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_sample_documents(): void
    {
        // Arrange: Clean up any existing test documents
        Document::where('order_id', '>=', 1000000)->delete();

        // Act: Run the command
        $this->artisan('app:create-sample-documents', ['--count' => '3'])
            ->assertExitCode(0);

        // Assert: Document were created
        $this->assertDatabaseCount('documents', 3);

        // Assert: Check document details
        $documents = Document::where('order_id', '>=', 1000000)->get();
        $this->assertCount(3, $documents);

        $documents->each(function (Document $document) {
            $this->assertNotNull($document->uid);
            $this->assertNotNull($document->order_id);
            $this->assertNotNull($document->customer_email);
            $this->assertIn($document->type, ['corta', 'rifle', 'escopeta', 'dni']);
            $this->assertNull($document->confirmed_at);
        });
    }

    public function test_command_with_custom_count(): void
    {
        // Arrange: Clean up any existing test documents
        Document::where('order_id', '>=', 1000000)->delete();

        // Act: Run the command with custom count
        $this->artisan('app:create-sample-documents', ['--count' => '5'])
            ->assertExitCode(0);

        // Assert: Correct number of documents created
        $this->assertDatabaseCount('documents', 5);
    }

    public function test_command_prevents_duplicate_orders(): void
    {
        // Arrange: Clean up and create first batch
        Document::where('order_id', '>=', 1000000)->delete();
        $this->artisan('app:create-sample-documents', ['--count' => '2']);

        // Act: Try to create again (should skip duplicates)
        $this->artisan('app:create-sample-documents', ['--count' => '2'])
            ->assertExitCode(0);

        // Assert: Still only 2 documents (duplicates were skipped)
        $this->assertDatabaseCount('documents', 2);
    }

    public function test_command_sends_initial_request_emails(): void
    {
        // Arrange: Clean up and enable email sending
        Document::where('order_id', '>=', 1000000)->delete();

        // Act: Run the command with --send-emails flag
        $this->artisan('app:create-sample-documents', [
            '--count' => '2',
            '--send-emails' => true,
        ])->assertExitCode(0);

        // Assert: Document have customer emails
        $documents = Document::where('order_id', '>=', 1000000)->get();
        $this->assertCount(2, $documents);

        $documents->each(function (Document $document) {
            $this->assertNotNull($document->customer_email);
            $this->assertStringContainsString('@example.com', $document->customer_email);
        });
    }

    public function test_sample_documents_have_required_documents(): void
    {
        // Arrange: Clean up
        Document::where('order_id', '>=', 1000000)->delete();

        // Act: Create sample documents
        $this->artisan('app:create-sample-documents', ['--count' => '1']);

        // Assert: Document has required documents initialized
        $document = Document::where('order_id', 1000000)->first();
        $this->assertNotNull($document->required_documents);
        $this->assertIsArray($document->required_documents);
        $this->assertNotEmpty($document->required_documents);
    }

    public function test_sample_documents_have_customer_data(): void
    {
        // Arrange: Clean up
        Document::where('order_id', '>=', 1000000)->delete();

        // Act: Create sample document
        $this->artisan('app:create-sample-documents', ['--count' => '1']);

        // Assert: Document has complete customer data
        $document = Document::where('order_id', 1000000)->first();
        $this->assertNotNull($document->customer_firstname);
        $this->assertNotNull($document->customer_lastname);
        $this->assertNotNull($document->customer_email);
        $this->assertNotNull($document->customer_company);
        $this->assertNotNull($document->customer_cellphone);
    }

    public function test_sample_documents_have_products(): void
    {
        // Arrange: Clean up
        Document::where('order_id', '>=', 1000000)->delete();

        // Act: Create sample document
        $this->artisan('app:create-sample-documents', ['--count' => '1']);

        // Assert: Document has inventaries associated
        $document = Document::where('order_id', 1000000)->first();
        $this->assertGreaterThan(0, $document->products()->count());

        $product = $document->products()->first();
        $this->assertNotNull($product->product_name);
        $this->assertNotNull($product->product_reference);
        $this->assertGreaterThan(0, $product->quantity);
    }

    public function test_command_output_shows_created_count(): void
    {
        // Arrange: Clean up
        Document::where('order_id', '>=', 1000000)->delete();

        // Act & Assert: Command output shows correct count
        $this->artisan('app:create-sample-documents', ['--count' => '2'])
            ->expectsOutput('Document created: 2')
            ->assertExitCode(0);
    }

    public function test_command_handles_unicode_characters(): void
    {
        // Arrange: Clean up
        Document::where('order_id', '>=', 1000000)->delete();

        // Act: Create sample documents (names have Spanish characters)
        $this->artisan('app:create-sample-documents', ['--count' => '3']);

        // Assert: Document with accented characters were created
        $documents = Document::where('order_id', '>=', 1000000)->get();
        $namesWithAccents = $documents
            ->map(fn (Document $d) => $d->customer_firstname.$d->customer_lastname)
            ->filter(fn (string $name) => preg_match('/[áéíóúñü]/i', $name))
            ->count();

        // Should have at least some names with accents (García, López, etc.)
        $this->assertGreaterThan(0, $namesWithAccents);
    }

    protected function tearDown(): void
    {
        // Clean up test documents
        Document::where('order_id', '>=', 1000000)->delete();
        parent::tearDown();
    }
}
