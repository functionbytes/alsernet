<?php

namespace Modules\Attention\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Attention\Tests\TestCase;

class AttentionFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function test_can_upload_files()
    {
        // Arrange
        $attention = $this->createAttention();
        $file = $this->createTestFile('documento.pdf', 1024);

        // Act
        $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
            'file' => $file,
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'file_name',
                    'size',
                    'mime_type',
                    'url',
                ],
            ]);

        $attention->refresh();
        $this->assertTrue($attention->hasDocuments());
        $this->assertEquals(1, $attention->documentsCount());
    }

    /** @test */
    public function test_can_upload_multiple_files()
    {
        // Arrange
        $attention = $this->createAttention();
        $file1 = $this->createTestFile('doc1.pdf', 1024);
        $file2 = $this->createTestFile('doc2.pdf', 1024);

        // Act
        $this->postJson("/api/peticiones/{$attention->radicado}/files", ['file' => $file1]);
        $this->postJson("/api/peticiones/{$attention->radicado}/files", ['file' => $file2]);

        // Assert
        $attention->refresh();
        $this->assertEquals(2, $attention->documentsCount());
    }

    /** @test */
    public function test_validates_file_is_required()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function test_validates_file_size()
    {
        // Arrange
        $attention = $this->createAttention();
        // Crear archivo mayor a 10MB (límite configurado)
        $largeFile = $this->createTestFile('large.pdf', 11 * 1024); // 11MB

        // Act
        $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
            'file' => $largeFile,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function test_validates_file_mime_types()
    {
        // Arrange
        $attention = $this->createAttention();
        $invalidFile = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

        // Act
        $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
            'file' => $invalidFile,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function test_accepts_pdf_files()
    {
        // Arrange
        $attention = $this->createAttention();
        $pdfFile = $this->createTestFile('documento.pdf', 100);

        // Act
        $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
            'file' => $pdfFile,
        ]);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function test_accepts_image_files()
    {
        // Arrange
        $attention = $this->createAttention();
        $imageFile = $this->createTestImage('foto.jpg');

        // Act
        $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
            'file' => $imageFile,
        ]);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function test_accepts_word_documents()
    {
        // Arrange
        $attention = $this->createAttention();
        $wordFile = UploadedFile::fake()->create(
            'documento.docx',
            100,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        // Act
        $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
            'file' => $wordFile,
        ]);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function test_can_list_files()
    {
        // Arrange
        $attention = $this->createAttention();
        $attention->addDocument($this->createTestFile('doc1.pdf'));
        $attention->addDocument($this->createTestFile('doc2.pdf'));

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}/files");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'name',
                        'file_name',
                        'size',
                        'mime_type',
                        'url',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_can_delete_file()
    {
        // Arrange
        $attention = $this->createAttention();
        $media = $attention->addDocument($this->createTestFile('doc.pdf'));

        // Act
        $response = $this->deleteJson("/api/peticiones/{$attention->radicado}/files/{$media->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Archivo eliminado correctamente',
            ]);

        $attention->refresh();
        $this->assertFalse($attention->hasDocuments());
    }

    /** @test */
    public function test_cannot_delete_non_existent_file()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $response = $this->deleteJson("/api/peticiones/{$attention->radicado}/files/99999");

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function test_cannot_delete_file_from_different_attention()
    {
        // Arrange
        $attention1 = $this->createAttention();
        $attention2 = $this->createAttention([
            'customer_email' => 'other@example.com',
        ]);

        $media = $attention1->addDocument($this->createTestFile('doc.pdf'));

        // Act - Intentar eliminar archivo de attention1 usando radicado de attention2
        $response = $this->deleteJson("/api/peticiones/{$attention2->radicado}/files/{$media->id}");

        // Assert
        $response->assertStatus(404);

        // Verificar que el archivo sigue existiendo
        $attention1->refresh();
        $this->assertTrue($attention1->hasDocuments());
    }

    /** @test */
    public function test_file_upload_returns_404_for_invalid_radicado()
    {
        // Arrange
        $file = $this->createTestFile('doc.pdf');

        // Act
        $response = $this->postJson('/api/peticiones/INVALID-RADICADO/files', [
            'file' => $file,
        ]);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function test_file_list_returns_empty_array_when_no_files()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act
        $response = $this->getJson("/api/peticiones/{$attention->radicado}/files");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /** @test */
    public function test_sanitizes_file_names()
    {
        // Arrange
        $attention = $this->createAttention();
        $file = UploadedFile::fake()->create('archivo con espacios y @#$.pdf', 100, 'application/pdf');

        // Act
        $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
            'file' => $file,
        ]);

        // Assert
        $response->assertStatus(201);

        $attention->refresh();
        $document = $attention->getFirstDocument();

        // Verificar que el nombre fue sanitizado
        $this->assertStringNotContainsString(' ', $document->file_name);
        $this->assertStringNotContainsString('@', $document->file_name);
    }

    /** @test */
    public function test_respects_throttle_limits_on_upload()
    {
        // Arrange
        $attention = $this->createAttention();

        // Act - Intentar subir más de 60 archivos en 1 minuto
        for ($i = 0; $i < 61; $i++) {
            $file = $this->createTestFile("doc{$i}.pdf", 10);
            $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
                'file' => $file,
            ]);
        }

        // Assert - El request 61 debe ser rechazado
        $response->assertStatus(429);
    }
}
