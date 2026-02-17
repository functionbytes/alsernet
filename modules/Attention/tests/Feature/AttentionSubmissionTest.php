<?php

namespace Modules\Attention\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Tests\TestCase;

class AttentionSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_submit_pqrsf_successfully()
    {
        // Arrange
        $data = $this->validAttentionData();

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uid',
                    'radicado',
                    'status',
                    'customer_firstname',
                    'customer_lastname',
                    'customer_email',
                    'subject',
                    'description',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('attentions', [
            'customer_email' => $data['customer_email'],
            'subject' => $data['subject'],
            'status' => AttentionStatus::RECEIVED->value,
        ]);

        // Verificar que se generó radicado
        $attention = Attention::where('customer_email', $data['customer_email'])->first();
        $this->assertNotNull($attention->radicado);
        $this->assertStringStartsWith('PQRSF-', $attention->radicado);
    }

    /** @test */
    public function test_can_submit_with_files()
    {
        // Arrange
        Storage::fake('public');
        $data = $this->validAttentionData();
        $file = $this->createTestFile('documento.pdf', 1024);

        $data['files'] = [$file];

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(201);

        $attention = Attention::where('customer_email', $data['customer_email'])->first();
        $this->assertTrue($attention->hasDocuments());
        $this->assertEquals(1, $attention->documentsCount());
    }

    /** @test */
    public function test_validates_required_fields()
    {
        // Act
        $response = $this->postJson('/api/pqrsf', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'type_id',
                'category_id',
                'subject',
                'description',
            ]);
    }

    /** @test */
    public function test_validates_email_format()
    {
        // Arrange
        $data = $this->validAttentionData([
            'customer_email' => 'correo-invalido',
        ]);

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    }

    /** @test */
    public function test_generates_unique_radicado()
    {
        // Arrange
        $data1 = $this->validAttentionData(['customer_email' => 'user1@example.com']);
        $data2 = $this->validAttentionData(['customer_email' => 'user2@example.com']);

        // Act
        $response1 = $this->postJson('/api/pqrsf', $data1);
        $response2 = $this->postJson('/api/pqrsf', $data2);

        // Assert
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $attention1 = Attention::where('customer_email', 'user1@example.com')->first();
        $attention2 = Attention::where('customer_email', 'user2@example.com')->first();

        $this->assertNotEquals($attention1->radicado, $attention2->radicado);
        $this->assertStringStartsWith('PQRSF-'.date('Y'), $attention1->radicado);
        $this->assertStringStartsWith('PQRSF-'.date('Y'), $attention2->radicado);
    }

    /** @test */
    public function test_can_submit_anonymous_pqrsf()
    {
        // Arrange
        $data = $this->validAnonymousAttentionData();

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.is_anonymous', true);

        $attention = Attention::where('subject', $data['subject'])->first();
        $this->assertTrue($attention->is_anonymous);
        $this->assertNull($attention->customer_firstname);
        $this->assertNull($attention->customer_lastname);
        $this->assertNull($attention->customer_email);
        $this->assertEquals('ANÓNIMO', $attention->full_name);
    }

    /** @test */
    public function test_validates_non_anonymous_requires_contact_info()
    {
        // Arrange
        $type = $this->createAttentionType();
        $category = $this->createCategory();
        $sede = $this->createSede();

        $data = [
            'type_id' => $type->id,
            'category_id' => $category->id,
            'sede_id' => $sede->id,
            'is_anonymous' => false,
            'subject' => 'Test',
            'description' => 'Test description',
            // Falta información de contacto
        ];

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function test_normalizes_customer_names_to_uppercase()
    {
        // Arrange
        $data = $this->validAttentionData([
            'customer_firstname' => 'carlos',
            'customer_lastname' => 'rodriguez',
        ]);

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(201);

        $attention = Attention::where('customer_email', $data['customer_email'])->first();
        $this->assertEquals('CARLOS', $attention->customer_firstname);
        $this->assertEquals('RODRIGUEZ', $attention->customer_lastname);
    }

    /** @test */
    public function test_creates_initial_action_log_on_submission()
    {
        // Arrange
        $data = $this->validAttentionData();

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(201);

        $attention = Attention::where('customer_email', $data['customer_email'])->first();
        $this->assertGreaterThan(0, $attention->actions()->count());
    }

    /** @test */
    public function test_subject_must_not_exceed_maximum_length()
    {
        // Arrange
        $data = $this->validAttentionData([
            'subject' => str_repeat('a', 256), // Asumiendo max 255
        ]);

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }

    /** @test */
    public function test_invalid_type_id_is_rejected()
    {
        // Arrange
        $data = $this->validAttentionData([
            'type_id' => 99999, // ID que no existe
        ]);

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type_id']);
    }

    /** @test */
    public function test_invalid_category_id_is_rejected()
    {
        // Arrange
        $data = $this->validAttentionData([
            'category_id' => 99999,
        ]);

        // Act
        $response = $this->postJson('/api/pqrsf', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    /** @test */
    public function test_throttle_limits_submission_rate()
    {
        // Arrange
        $data = $this->validAttentionData();

        // Act - Hacer más de 60 requests en 1 minuto
        for ($i = 0; $i < 61; $i++) {
            $response = $this->postJson('/api/pqrsf', array_merge($data, [
                'customer_email' => "user{$i}@example.com",
            ]));
        }

        // Assert - El request 61 debe ser rechazado
        $response->assertStatus(429);
    }
}
