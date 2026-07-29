<?php

namespace Tests\Feature\Managers\Settings\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mailer\Models\MailerTemplate;
use Tests\TestCase;

class DocumentConfigurationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que la página de configuración global de documentos carga correctamente
     */
    public function test_global_settings_page_loads(): void
    {
        $response = $this->get(route('manager.backups.documents.configurations.global'));

        $response->assertStatus(200);
        $response->assertViewIs('theme.views.backups.documents.configurations.index');
        $response->assertViewHas('globalSettings');
    }

    /**
     * Test que el endpoint de búsqueda de templates retorna resultados correctamente
     */
    public function test_search_templates_returns_all_templates(): void
    {
        // Crear algunas plantillas de prueba
        MailerTemplate::factory()->create([
            'name' => 'Solicitud Inicial',
            'module' => 'documents',
            'is_enabled' => true,
        ]);

        MailerTemplate::factory()->create([
            'name' => 'Recordatorio de Documentos',
            'module' => 'documents',
            'is_enabled' => true,
        ]);

        $response = $this->getJson(
            route('manager.backups.documents.configurations.search-templates'),
            ['q' => '']
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => ['id', 'text', 'name', 'key', 'lang_id', 'lang_name'],
            ],
            'pagination',
        ]);

        // Verificar que al menos hay 2 resultados (las que creamos)
        $this->assertGreaterThanOrEqual(2, count($response->json('results')));
    }

    /**
     * Test que la búsqueda de templates filtra por término de búsqueda
     */
    public function test_search_templates_filters_by_search_term(): void
    {
        MailerTemplate::factory()->create([
            'name' => 'Solicitud Inicial de Documentos',
            'module' => 'documents',
            'is_enabled' => true,
        ]);

        MailerTemplate::factory()->create([
            'name' => 'Aprobación de Documentos',
            'module' => 'documents',
            'is_enabled' => true,
        ]);

        $response = $this->getJson(
            route('manager.backups.documents.configurations.search-templates'),
            ['q' => 'Solicitud']
        );

        $response->assertStatus(200);

        // Debe tener al menos un resultado que contenga "Solicitud"
        $results = $response->json('results');
        $this->assertGreaterThan(0, count($results));

        // Verificar que los resultados contienen "Solicitud"
        $foundSolicitud = false;
        foreach ($results as $result) {
            if (strpos($result['name'], 'Solicitud') !== false) {
                $foundSolicitud = true;
                break;
            }
        }
        $this->assertTrue($foundSolicitud, 'Should find templates with "Solicitud" in the name');
    }

    /**
     * Test que solo retorna templates habilitados
     */
    public function test_search_templates_only_returns_enabled(): void
    {
        MailerTemplate::factory()->create([
            'name' => 'Plantilla Habilitada',
            'module' => 'documents',
            'is_enabled' => true,
        ]);

        MailerTemplate::factory()->create([
            'name' => 'Plantilla Deshabilitada',
            'module' => 'documents',
            'is_enabled' => false,
        ]);

        $response = $this->getJson(
            route('manager.backups.documents.configurations.search-templates'),
            ['q' => '']
        );

        $response->assertStatus(200);

        // Verificar que "Plantilla Deshabilitada" no está en los resultados
        $results = $response->json('results');
        foreach ($results as $result) {
            $this->assertNotContains('Deshabilitada', $result['name']);
        }
    }

    /**
     * Test que solo retorna templates del módulo 'documents'
     */
    public function test_search_templates_only_returns_documents_module(): void
    {
        MailerTemplate::factory()->create([
            'name' => 'Plantilla de Documentos',
            'module' => 'documents',
            'is_enabled' => true,
        ]);

        MailerTemplate::factory()->create([
            'name' => 'Plantilla de Órdenes',
            'module' => 'orders',
            'is_enabled' => true,
        ]);

        $response = $this->getJson(
            route('manager.backups.documents.configurations.search-templates'),
            ['q' => '']
        );

        $response->assertStatus(200);

        // Verificar que "Plantilla de Órdenes" no está en los resultados
        $results = $response->json('results');
        foreach ($results as $result) {
            $this->assertNotContains('Órdenes', $result['name']);
        }
    }

    /**
     * Test que el template resultado incluye información del idioma
     */
    public function test_search_templates_includes_language_info(): void
    {
        $template = MailerTemplate::factory()->create([
            'name' => 'Plantilla con Idioma',
            'module' => 'documents',
            'is_enabled' => true,
            'lang_id' => 1, // Español
        ]);

        $response = $this->getJson(
            route('manager.backups.documents.configurations.search-templates'),
            ['q' => 'Idioma']
        );

        $response->assertStatus(200);

        $results = $response->json('results');
        $this->assertGreaterThan(0, count($results));

        // Verificar que incluye lang_id
        $foundTemplate = null;
        foreach ($results as $result) {
            if ($result['id'] === $template->id) {
                $foundTemplate = $result;
                break;
            }
        }

        $this->assertNotNull($foundTemplate);
        $this->assertEquals(1, $foundTemplate['lang_id']);
        $this->assertNotEmpty($foundTemplate['lang_name']);
    }

    /**
     * Test que la respuesta tiene la estructura correcta en el formato Select2
     */
    public function test_search_templates_returns_select2_format(): void
    {
        MailerTemplate::factory()->create([
            'name' => 'Test Template',
            'module' => 'documents',
            'is_enabled' => true,
        ]);

        $response = $this->getJson(
            route('manager.backups.documents.configurations.search-templates'),
            ['q' => 'Test']
        );

        $response->assertStatus(200);

        // Verificar estructura Select2
        $results = $response->json('results');
        if (count($results) > 0) {
            $first = $results[0];

            // El formato Select2 requiere 'id' y 'text'
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('text', $first);
            $this->assertIsInt($first['id']);
            $this->assertIsString($first['text']);
            // El 'text' debe incluir el idioma entre corchetes
            $this->assertStringContainsString('[', $first['text']);
            $this->assertStringContainsString(']', $first['text']);
        }
    }
}
