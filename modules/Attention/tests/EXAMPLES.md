# Ejemplos de Uso de Tests

Este documento proporciona ejemplos prácticos de cómo escribir y ejecutar tests para el módulo Attention.

## Tabla de Contenidos

1. [Escribir un Test Básico](#escribir-un-test-básico)
2. [Tests con Autenticación](#tests-con-autenticación)
3. [Tests con Archivos](#tests-con-archivos)
4. [Tests de Validación](#tests-de-validación)
5. [Tests de Scopes](#tests-de-scopes)
6. [Tests de Relaciones](#tests-de-relaciones)
7. [Mocking y Fakes](#mocking-y-fakes)

---

## Escribir un Test Básico

### Ejemplo: Test de Creación de peticiones

```php
<?php

namespace Modules\Attention\Tests\Feature;

use Modules\Attention\Tests\TestCase;
use Modules\Attention\Models\Attention;

class MiNuevoTest extends TestCase
{
    /** @test */
    public function test_puede_crear_un_peticiones()
    {
        // Arrange - Preparar datos
        $data = $this->validAttentionData([
            'subject' => 'Mi solicitud de prueba',
        ]);

        // Act - Ejecutar acción
        $response = $this->postJson('/api/peticiones', $data);

        // Assert - Verificar resultados
        $response->assertStatus(201);

        $this->assertDatabaseHas('attentions', [
            'subject' => 'Mi solicitud de prueba',
        ]);
    }
}
```

---

## Tests con Autenticación

### Ejemplo: Test que requiere usuario autenticado

```php
/** @test */
public function test_usuario_autenticado_puede_ver_lista()
{
    // Arrange
    $this->actingAsUser();
    $this->createAttention(['subject' => 'Test 1']);
    $this->createAttention(['subject' => 'Test 2']);

    // Act
    $response = $this->getJson('/api/settings/peticiones');

    // Assert
    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
}
```

### Ejemplo: Test que requiere admin

```php
/** @test */
public function test_solo_admin_puede_cerrar_peticiones()
{
    // Arrange
    $this->actingAsAdmin();
    $attention = $this->createAttention();

    // Act
    $response = $this->postJson("/api/settings/peticiones/{$attention->radicado}/close", [
        'comment' => 'Cerrando caso',
    ]);

    // Assert
    $response->assertStatus(200);

    $attention->refresh();
    $this->assertTrue($attention->isClosed());
}
```

### Ejemplo: Test de acceso denegado

```php
/** @test */
public function test_usuario_no_autenticado_no_puede_acceder()
{
    // Act - Sin autenticación
    $response = $this->getJson('/api/settings/peticiones');

    // Assert
    $response->assertStatus(401);
}
```

---

## Tests con Archivos

### Ejemplo: Subir archivo PDF

```php
/** @test */
public function test_puede_subir_documento_pdf()
{
    // Arrange
    Storage::fake('public');
    $attention = $this->createAttention();
    $file = $this->createTestFile('contrato.pdf', 2048);

    // Act
    $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
        'file' => $file,
    ]);

    // Assert
    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'name', 'url']]);

    $attention->refresh();
    $this->assertTrue($attention->hasDocuments());
}
```

### Ejemplo: Subir imagen

```php
/** @test */
public function test_puede_subir_imagen()
{
    // Arrange
    Storage::fake('public');
    $attention = $this->createAttention();
    $image = $this->createTestImage('evidencia.jpg');

    // Act
    $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
        'file' => $image,
    ]);

    // Assert
    $response->assertStatus(201);

    $document = $attention->getFirstDocument();
    $this->assertNotNull($document);
    $this->assertStringContainsString('image/', $document->mime_type);
}
```

### Ejemplo: Validar tamaño máximo

```php
/** @test */
public function test_rechaza_archivos_muy_grandes()
{
    // Arrange
    $attention = $this->createAttention();
    $largeFile = $this->createTestFile('archivo_grande.pdf', 11 * 1024); // 11MB

    // Act
    $response = $this->postJson("/api/peticiones/{$attention->radicado}/files", [
        'file' => $largeFile,
    ]);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
}
```

---

## Tests de Validación

### Ejemplo: Validar campos requeridos

```php
/** @test */
public function test_valida_campos_obligatorios()
{
    // Act - Enviar request vacío
    $response = $this->postJson('/api/peticiones', []);

    // Assert - Verificar que todos los campos requeridos se validan
    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'type_id',
            'category_id',
            'sede_id',
            'subject',
            'description',
        ]);
}
```

### Ejemplo: Validar formato de email

```php
/** @test */
public function test_valida_formato_email()
{
    // Arrange
    $data = $this->validAttentionData([
        'customer_email' => 'email-invalido',
    ]);

    // Act
    $response = $this->postJson('/api/peticiones', $data);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['customer_email'])
        ->assertJsonPath('errors.customer_email.0', 'El correo electrónico debe ser válido.');
}
```

### Ejemplo: Validar longitud máxima

```php
/** @test */
public function test_subject_no_puede_exceder_255_caracteres()
{
    // Arrange
    $data = $this->validAttentionData([
        'subject' => str_repeat('a', 256),
    ]);

    // Act
    $response = $this->postJson('/api/peticiones', $data);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['subject']);
}
```

---

## Tests de Scopes

### Ejemplo: Test de scope personalizado

```php
/** @test */
public function test_scope_filtra_por_estado()
{
    // Arrange
    $this->createAttention(['status' => AttentionStatus::RECEIVED]);
    $this->createAttention(['status' => AttentionStatus::RECEIVED]);
    $this->createAttention(['status' => AttentionStatus::IN_PROCESS]);
    $this->createAttention(['status' => AttentionStatus::RESOLVED]);

    // Act
    $received = Attention::byStatus(AttentionStatus::RECEIVED)->get();
    $inProcess = Attention::inProcess()->get();
    $resolved = Attention::resolved()->get();

    // Assert
    $this->assertCount(2, $received);
    $this->assertCount(1, $inProcess);
    $this->assertCount(1, $resolved);
}
```

### Ejemplo: Test de búsqueda

```php
/** @test */
public function test_scope_search_busca_en_multiples_campos()
{
    // Arrange
    $this->createAttention(['subject' => 'Solicitud importante']);
    $this->createAttention(['description' => 'Descripción importante']);
    $this->createAttention(['customer_email' => 'importante@example.com']);
    $this->createAttention(['subject' => 'Otra cosa']);

    // Act
    $results = Attention::search('importante')->get();

    // Assert
    $this->assertCount(3, $results);
}
```

---

## Tests de Relaciones

### Ejemplo: Test de relación BelongsTo

```php
/** @test */
public function test_attention_pertenece_a_un_tipo()
{
    // Arrange
    $type = $this->createAttentionType(['name' => 'Petición']);
    $attention = $this->createAttention(['type_id' => $type->id]);

    // Act & Assert
    $this->assertInstanceOf(AttentionType::class, $attention->type);
    $this->assertEquals('Petición', $attention->type->name);
}
```

### Ejemplo: Test de relación HasMany

```php
/** @test */
public function test_attention_tiene_multiples_notas()
{
    // Arrange
    $attention = $this->createAttention();
    $user = $this->createUser();

    // Act
    $attention->addNote('Primera nota', $user->id);
    $attention->addNote('Segunda nota', $user->id);
    $attention->addNote('Tercera nota', $user->id);

    // Assert
    $this->assertCount(3, $attention->notes);
    $this->assertInstanceOf(AttentionNote::class, $attention->notes->first());
}
```

---

## Mocking y Fakes

### Ejemplo: Fake de Storage

```php
/** @test */
public function test_archivos_se_almacenan_correctamente()
{
    // Arrange
    Storage::fake('public');
    $attention = $this->createAttention();
    $file = $this->createTestFile('test.pdf');

    // Act
    $attention->addDocument($file);

    // Assert
    $this->assertTrue($attention->hasDocuments());

    // Verificar que el archivo fue guardado
    $document = $attention->getFirstDocument();
    $this->assertNotNull($document);
}
```

### Ejemplo: Fake de Notificaciones

```php
/** @test */
public function test_envia_notificacion_cuando_se_resuelve()
{
    // Arrange
    Notification::fake();
    $this->actingAsAdmin();
    $attention = $this->createAttention();

    // Act
    $attention->resolve('Resuelto', ResponseType::EMAIL);

    // Assert
    Notification::assertSentTo(
        $attention,
        AttentionResolvedNotification::class
    );
}
```

### Ejemplo: Fake de Queue

```php
/** @test */
public function test_job_se_encola_al_crear_peticiones()
{
    // Arrange
    Queue::fake();
    $data = $this->validAttentionData();

    // Act
    $this->postJson('/api/peticiones', $data);

    // Assert
    Queue::assertPushed(ProcessAttentionJob::class);
}
```

---

## Tips y Mejores Prácticas

### 1. Usar Factories en lugar de crear datos manualmente

❌ **Mal:**
```php
$attention = Attention::create([
    'uid' => Str::uuid(),
    'radicado' => 'peticiones-2026-000001',
    'type_id' => 1,
    // ... muchos más campos
]);
```

✅ **Bien:**
```php
$attention = $this->createAttention(['subject' => 'Custom']);
```

### 2. Nombrar tests descriptivamente

❌ **Mal:**
```php
public function test_1() { ... }
public function test_create() { ... }
```

✅ **Bien:**
```php
public function test_puede_crear_peticiones_con_datos_validos() { ... }
public function test_rechaza_peticiones_sin_email_cuando_no_es_anonimo() { ... }
```

### 3. Agrupar assertions relacionadas

❌ **Mal:**
```php
$this->assertEquals(1, $attention->id);
// 50 líneas de código después...
$this->assertEquals('Petición', $attention->type->name);
```

✅ **Bien:**
```php
// Verificar atributos del modelo
$this->assertEquals(1, $attention->id);
$this->assertEquals('Test', $attention->subject);

// Verificar relaciones
$this->assertEquals('Petición', $attention->type->name);
$this->assertEquals('Servicios', $attention->category->name);
```

### 4. Limpiar datos después de cada test

```php
use RefreshDatabase; // En la clase de test
```

Esto asegura que cada test inicie con una base de datos limpia.

### 5. Tests independientes

❌ **Mal:**
```php
// Test 1 crea datos
public function test_crear() {
    $this->attention = Attention::create(...);
}

// Test 2 depende de Test 1
public function test_actualizar() {
    $this->attention->update(...);
}
```

✅ **Bien:**
```php
public function test_puede_actualizar() {
    $attention = $this->createAttention();
    $attention->update(['subject' => 'Nuevo']);
    $this->assertEquals('Nuevo', $attention->fresh()->subject);
}
```

---

## Comandos Útiles

```bash
# Ejecutar test específico
vendor/bin/phpunit --filter test_puede_crear_peticiones

# Ejecutar con output detallado
vendor/bin/phpunit --testdox

# Ejecutar con cobertura
vendor/bin/phpunit --coverage-text

# Ejecutar solo tests rápidos
vendor/bin/phpunit --exclude-group slow

# Ver lista de tests sin ejecutarlos
vendor/bin/phpunit --list-tests
```

---

## Recursos Adicionales

- [Documentación PHPUnit](https://phpunit.de/documentation.html)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Pest PHP](https://pestphp.com/) - Alternativa moderna a PHPUnit
- [Test Driven Laravel](https://course.testdrivenlaravel.com/)
