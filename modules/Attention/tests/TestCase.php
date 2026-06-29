<?php

namespace Modules\Attention\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionDepartment;
use Modules\Attention\Models\AttentionSede;
use Modules\Attention\Models\AttentionType;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar Storage fake para tests de archivos
        Storage::fake('public');

        // Cargar configuración del módulo
        $this->app['config']->set('attention', [
            'radicado_prefix' => 'peticiones',
            'attachments' => [
                'max_size' => 10 * 1024 * 1024,
                'allowed_mimes' => [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/jpg',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
            ],
        ]);
    }

    /**
     * Helper: Crear un tipo de atención
     */
    protected function createAttentionType(array $attributes = []): AttentionType
    {
        return AttentionType::create(array_merge([
            'name' => 'Petición',
            'code' => 'P',
            'description' => 'Solicitud de información o servicios',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Helper: Crear una categoría
     */
    protected function createCategory(array $attributes = []): AttentionCategory
    {
        return AttentionCategory::create(array_merge([
            'name' => 'Servicios Generales',
            'description' => 'Categoría general',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Helper: Crear una sede
     */
    protected function createSede(array $attributes = []): AttentionSede
    {
        return AttentionSede::create(array_merge([
            'name' => 'Sede Principal',
            'code' => 'SP',
            'address' => 'Calle 123',
            'city' => 'Bogotá',
            'phone' => '3001234567',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Helper: Crear un departamento
     */
    protected function createDepartment(array $attributes = []): AttentionDepartment
    {
        return AttentionDepartment::create(array_merge([
            'name' => 'Atención al Cliente',
            'code' => 'AC',
            'description' => 'Departamento de atención',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Helper: Crear un usuario autenticado
     */
    protected function createUser(array $attributes = [])
    {
        return User::factory()->create($attributes);
    }

    /**
     * Helper: Crear un peticiones completo con relaciones
     */
    protected function createAttention(array $attributes = []): Attention
    {
        $type = $this->createAttentionType();
        $category = $this->createCategory();
        $sede = $this->createSede();

        return Attention::create(array_merge([
            'uid' => (string) Str::uuid(),
            'radicado' => Attention::generateRadicado(),
            'type_id' => $type->id,
            'category_id' => $category->id,
            'sede_id' => $sede->id,
            'customer_firstname' => 'Juan',
            'customer_lastname' => 'Pérez',
            'customer_email' => 'juan.perez@example.com',
            'customer_cellphone' => '3001234567',
            'customer_dni' => '1234567890',
            'customer_address' => 'Calle 123',
            'is_anonymous' => false,
            'subject' => 'Solicitud de información',
            'description' => 'Descripción detallada de la solicitud',
            'status' => AttentionStatus::RECEIVED,
        ], $attributes));
    }

    /**
     * Helper: Crear peticiones anónimo
     */
    protected function createAnonymousAttention(array $attributes = []): Attention
    {
        return $this->createAttention(array_merge([
            'customer_firstname' => null,
            'customer_lastname' => null,
            'customer_email' => null,
            'customer_cellphone' => null,
            'customer_dni' => null,
            'customer_address' => null,
            'is_anonymous' => true,
        ], $attributes));
    }

    /**
     * Helper: Autenticar como usuario
     */
    protected function actingAsUser($user = null)
    {
        $user = $user ?? $this->createUser();

        return $this->actingAs($user, 'sanctum');
    }

    /**
     * Helper: Autenticar como settings
     */
    protected function actingAsAdmin()
    {
        $admin = $this->createUser(['role' => 'settings']);

        return $this->actingAs($admin, 'sanctum');
    }

    /**
     * Helper: Crear archivo de prueba
     */
    protected function createTestFile($name = 'test.pdf', $size = 1024)
    {
        return UploadedFile::fake()->create($name, $size, 'application/pdf');
    }

    /**
     * Helper: Crear imagen de prueba
     */
    protected function createTestImage($name = 'test.jpg')
    {
        return UploadedFile::fake()->image($name);
    }

    /**
     * Helper: Datos válidos para crear peticiones
     */
    protected function validAttentionData(array $overrides = []): array
    {
        $type = $this->createAttentionType();
        $category = $this->createCategory();
        $sede = $this->createSede();

        return array_merge([
            'type_id' => $type->id,
            'category_id' => $category->id,
            'sede_id' => $sede->id,
            'customer_firstname' => 'María',
            'customer_lastname' => 'García',
            'customer_email' => 'maria.garcia@example.com',
            'customer_cellphone' => '3109876543',
            'customer_dni' => '9876543210',
            'customer_address' => 'Carrera 45 #67-89',
            'is_anonymous' => false,
            'subject' => 'Consulta sobre servicios',
            'description' => 'Me gustaría obtener información detallada sobre los servicios disponibles.',
        ], $overrides);
    }

    /**
     * Helper: Datos válidos para peticiones anónimo
     */
    protected function validAnonymousAttentionData(array $overrides = []): array
    {
        $type = $this->createAttentionType();
        $category = $this->createCategory();
        $sede = $this->createSede();

        return array_merge([
            'type_id' => $type->id,
            'category_id' => $category->id,
            'sede_id' => $sede->id,
            'is_anonymous' => true,
            'subject' => 'Reporte anónimo',
            'description' => 'Descripción de un reporte realizado de forma anónima.',
        ], $overrides);
    }
}
