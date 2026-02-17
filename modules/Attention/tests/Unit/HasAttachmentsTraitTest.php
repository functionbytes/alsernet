<?php

namespace Modules\Attention\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Attention\Tests\TestCase;

class HasAttachmentsTraitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function test_can_add_document()
    {
        // Arrange
        $attention = $this->createAttention();
        $file = $this->createTestFile('test.pdf', 100);

        // Act
        $media = $attention->addDocument($file);

        // Assert
        $this->assertNotNull($media);
        $this->assertInstanceOf(\Spatie\MediaLibrary\MediaCollections\Models\Media::class, $media);
        $this->assertEquals('documents', $media->collection_name);
    }

    /** @test */
    public function test_can_add_document_with_custom_name()
    {
        // Arrange
        $attention = $this->createAttention();
        $file = $this->createTestFile('original.pdf', 100);

        // Act
        $media = $attention->addDocument($file, 'Custom Document Name');

        // Assert
        $this->assertEquals('Custom Document Name', $media->name);
    }

    /** @test */
    public function test_can_get_documents_array()
    {
        // Arrange
        $attention = $this->createAttention();
        $attention->addDocument($this->createTestFile('doc1.pdf'), 'Document 1');
        $attention->addDocument($this->createTestFile('doc2.pdf'), 'Document 2');

        // Act
        $documents = $attention->getDocumentsArray();

        // Assert
        $this->assertIsArray($documents);
        $this->assertCount(2, $documents);

        // Verificar estructura del primer documento
        $doc = $documents[0];
        $this->assertArrayHasKey('id', $doc);
        $this->assertArrayHasKey('uuid', $doc);
        $this->assertArrayHasKey('name', $doc);
        $this->assertArrayHasKey('file_name', $doc);
        $this->assertArrayHasKey('size', $doc);
        $this->assertArrayHasKey('human_size', $doc);
        $this->assertArrayHasKey('mime_type', $doc);
        $this->assertArrayHasKey('url', $doc);
        $this->assertArrayHasKey('extension', $doc);
        $this->assertArrayHasKey('created_at', $doc);
    }

    /** @test */
    public function test_can_remove_document()
    {
        // Arrange
        $attention = $this->createAttention();
        $media = $attention->addDocument($this->createTestFile('test.pdf'));

        // Act
        $result = $attention->removeDocument($media->id);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse($attention->hasDocuments());
        $this->assertEquals(0, $attention->documentsCount());
    }

    /** @test */
    public function test_remove_document_returns_false_for_non_existent_id()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $result = $attention->removeDocument(99999);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_can_check_has_documents()
    {
        // Arrange
        $attention = $this->createAttention();

        // Assert - Sin documentos
        $this->assertFalse($attention->hasDocuments());

        // Act - Agregar documento
        $attention->addDocument($this->createTestFile('test.pdf'));

        // Assert - Con documentos
        $this->assertTrue($attention->hasDocuments());
    }

    /** @test */
    public function test_documents_count()
    {
        // Arrange
        $attention = $this->createAttention();

        // Assert - Sin documentos
        $this->assertEquals(0, $attention->documentsCount());

        // Act - Agregar documentos
        $attention->addDocument($this->createTestFile('doc1.pdf'));
        $attention->addDocument($this->createTestFile('doc2.pdf'));
        $attention->addDocument($this->createTestFile('doc3.pdf'));

        // Assert - Con documentos
        $this->assertEquals(3, $attention->documentsCount());
    }

    /** @test */
    public function test_can_get_documents()
    {
        // Arrange
        $attention = $this->createAttention();
        $attention->addDocument($this->createTestFile('doc1.pdf'));
        $attention->addDocument($this->createTestFile('doc2.pdf'));

        // Act
        $documents = $attention->getDocuments();

        // Assert
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $documents);
        $this->assertCount(2, $documents);

        foreach ($documents as $doc) {
            $this->assertInstanceOf(\Spatie\MediaLibrary\MediaCollections\Models\Media::class, $doc);
        }
    }

    /** @test */
    public function test_can_clear_documents()
    {
        // Arrange
        $attention = $this->createAttention();
        $attention->addDocument($this->createTestFile('doc1.pdf'));
        $attention->addDocument($this->createTestFile('doc2.pdf'));

        // Act
        $attention->clearDocuments();

        // Assert
        $this->assertFalse($attention->hasDocuments());
        $this->assertEquals(0, $attention->documentsCount());
    }

    /** @test */
    public function test_can_get_first_document()
    {
        // Arrange
        $attention = $this->createAttention();
        $first = $attention->addDocument($this->createTestFile('first.pdf'), 'First');
        $attention->addDocument($this->createTestFile('second.pdf'), 'Second');

        // Act
        $firstDocument = $attention->getFirstDocument();

        // Assert
        $this->assertNotNull($firstDocument);
        $this->assertEquals($first->id, $firstDocument->id);
        $this->assertEquals('First', $firstDocument->name);
    }

    /** @test */
    public function test_get_first_document_returns_null_when_no_documents()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $firstDocument = $attention->getFirstDocument();

        // Assert
        $this->assertNull($firstDocument);
    }

    /** @test */
    public function test_can_get_first_document_url()
    {
        // Arrange
        $attention = $this->createAttention();
        $attention->addDocument($this->createTestFile('test.pdf'));

        // Act
        $url = $attention->getFirstDocumentUrl();

        // Assert
        $this->assertNotNull($url);
        $this->assertIsString($url);
    }

    /** @test */
    public function test_get_first_document_url_returns_null_when_no_documents()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $url = $attention->getFirstDocumentUrl();

        // Assert
        $this->assertNull($url);
    }

    /** @test */
    public function test_can_check_has_specific_document()
    {
        // Arrange
        $attention = $this->createAttention();
        $media = $attention->addDocument($this->createTestFile('test.pdf'));

        // Assert
        $this->assertTrue($attention->hasDocument($media->id));
        $this->assertFalse($attention->hasDocument(99999));
    }

    /** @test */
    public function test_can_get_specific_document_by_id()
    {
        // Arrange
        $attention = $this->createAttention();
        $media = $attention->addDocument($this->createTestFile('test.pdf'), 'Test Document');

        // Act
        $document = $attention->getDocument($media->id);

        // Assert
        $this->assertNotNull($document);
        $this->assertEquals($media->id, $document->id);
        $this->assertEquals('Test Document', $document->name);
    }

    /** @test */
    public function test_get_document_returns_null_for_non_existent_id()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $document = $attention->getDocument(99999);

        // Assert
        $this->assertNull($document);
    }

    /** @test */
    public function test_sanitizes_file_names()
    {
        // Arrange
        $attention = $this->createAttention();
        $file = $this->createTestFile('archivo con espacios @#$.pdf');

        // Act
        $media = $attention->addDocument($file);

        // Assert
        $this->assertStringNotContainsString(' ', $media->file_name);
        $this->assertStringNotContainsString('@', $media->file_name);
        $this->assertStringNotContainsString('#', $media->file_name);
        $this->assertStringNotContainsString('$', $media->file_name);
    }

    /** @test */
    public function test_documents_belong_to_correct_collection()
    {
        // Arrange
        $attention = $this->createAttention();
        $media = $attention->addDocument($this->createTestFile('test.pdf'));

        // Assert
        $this->assertEquals('documents', $media->collection_name);
    }

    /** @test */
    public function test_cannot_remove_document_from_different_model()
    {
        // Arrange
        $attention1 = $this->createAttention();
        $attention2 = $this->createAttention(['customer_email' => 'other@example.com']);

        $media = $attention1->addDocument($this->createTestFile('test.pdf'));

        // Act - Intentar eliminar desde otro modelo
        $result = $attention2->removeDocument($media->id);

        // Assert
        $this->assertFalse($result);

        // Verificar que el documento original sigue existiendo
        $this->assertTrue($attention1->hasDocument($media->id));
    }

    /** @test */
    public function test_accepts_configured_mime_types()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act & Assert - PDF
        $pdfMedia = $attention->addDocument($this->createTestFile('test.pdf', 100));
        $this->assertEquals('application/pdf', $pdfMedia->mime_type);

        // Act & Assert - Imágenes
        $jpgMedia = $attention->addDocument($this->createTestImage('test.jpg'));
        $this->assertStringContainsString('image/', $jpgMedia->mime_type);
    }

    /** @test */
    public function test_respects_max_file_size_configuration()
    {
        // Arrange
        $attention = $this->createAttention();

        // El límite está en 10MB según configuración
        // Este test verifica que archivos pequeños son aceptados
        $smallFile = $this->createTestFile('small.pdf', 1024); // 1MB

        // Act
        $media = $attention->addDocument($smallFile);

        // Assert
        $this->assertNotNull($media);
        $this->assertLessThan(10 * 1024 * 1024, $media->size);
    }

    /** @test */
    public function test_documents_array_includes_human_readable_size()
    {
        // Arrange
        $attention = $this->createAttention();
        $attention->addDocument($this->createTestFile('test.pdf', 1024)); // 1MB

        // Act
        $documents = $attention->getDocumentsArray();

        // Assert
        $this->assertArrayHasKey('human_size', $documents[0]);
        $this->assertNotEmpty($documents[0]['human_size']);
    }

    /** @test */
    public function test_documents_array_includes_creation_timestamp()
    {
        // Arrange
        $attention = $this->createAttention();
        $attention->addDocument($this->createTestFile('test.pdf'));

        // Act
        $documents = $attention->getDocumentsArray();

        // Assert
        $this->assertArrayHasKey('created_at', $documents[0]);
        $this->assertMatchesRegularExpression('/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/', $documents[0]['created_at']);
    }
}
