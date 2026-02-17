# Tests del Módulo Attention

Suite completa de tests para el módulo PQRSF (Attention).

## Estructura de Tests

```
tests/
├── Feature/              # Tests de integración (API endpoints)
│   ├── AttentionSubmissionTest.php
│   ├── AttentionTrackingTest.php
│   ├── AttentionFileUploadTest.php
│   └── AttentionAdminTest.php
├── Unit/                 # Tests unitarios (modelos y traits)
│   ├── AttentionModelTest.php
│   └── HasAttachmentsTraitTest.php
├── TestCase.php          # Clase base con helpers
└── README.md            # Este archivo
```

## Requisitos Previos

1. PHP >= 8.1
2. Composer instalado
3. Base de datos configurada (SQLite en memoria para tests)

## Instalación

```bash
# Instalar dependencias
composer install

# Ejecutar migraciones de prueba
php artisan migrate --env=testing
```

## Ejecutar Tests

### Todos los tests del módulo

```bash
cd modules/Attention
vendor/bin/phpunit
```

O desde la raíz del proyecto:

```bash
vendor/bin/phpunit modules/Attention/tests
```

### Tests específicos

**Solo tests Feature (API):**
```bash
vendor/bin/phpunit --testsuite Feature
# o
vendor/bin/phpunit tests/Feature
```

**Solo tests Unit:**
```bash
vendor/bin/phpunit --testsuite Unit
# o
vendor/bin/phpunit tests/Unit
```

**Un archivo específico:**
```bash
vendor/bin/phpunit tests/Feature/AttentionSubmissionTest.php
```

**Un test específico:**
```bash
vendor/bin/phpunit --filter test_can_submit_pqrsf_successfully
```

### Con coverage

```bash
vendor/bin/phpunit --coverage-html build/coverage
```

Luego abrir `build/coverage/index.html` en el navegador.

## Tests Disponibles

### Feature Tests (API)

#### AttentionSubmissionTest (13 tests)
- ✓ test_can_submit_pqrsf_successfully
- ✓ test_can_submit_with_files
- ✓ test_validates_required_fields
- ✓ test_validates_email_format
- ✓ test_generates_unique_radicado
- ✓ test_can_submit_anonymous_pqrsf
- ✓ test_validates_non_anonymous_requires_contact_info
- ✓ test_normalizes_customer_names_to_uppercase
- ✓ test_creates_initial_action_log_on_submission
- ✓ test_subject_must_not_exceed_maximum_length
- ✓ test_invalid_type_id_is_rejected
- ✓ test_invalid_category_id_is_rejected
- ✓ test_throttle_limits_submission_rate

#### AttentionTrackingTest (12 tests)
- ✓ test_can_track_by_radicado
- ✓ test_returns_404_for_invalid_radicado
- ✓ test_tracking_shows_current_status
- ✓ test_tracking_includes_type_and_category
- ✓ test_tracking_includes_sede_information
- ✓ test_tracking_hides_sensitive_admin_data
- ✓ test_tracking_shows_resolution_when_resolved
- ✓ test_tracking_shows_documents_count
- ✓ test_tracking_protects_anonymous_user_data
- ✓ test_tracking_is_case_insensitive
- ✓ test_tracking_includes_creation_date
- ✓ test_tracking_respects_throttle_limits

#### AttentionFileUploadTest (17 tests)
- ✓ test_can_upload_files
- ✓ test_can_upload_multiple_files
- ✓ test_validates_file_is_required
- ✓ test_validates_file_size
- ✓ test_validates_file_mime_types
- ✓ test_accepts_pdf_files
- ✓ test_accepts_image_files
- ✓ test_accepts_word_documents
- ✓ test_can_list_files
- ✓ test_can_delete_file
- ✓ test_cannot_delete_non_existent_file
- ✓ test_cannot_delete_file_from_different_attention
- ✓ test_file_upload_returns_404_for_invalid_radicado
- ✓ test_file_list_returns_empty_array_when_no_files
- ✓ test_sanitizes_file_names
- ✓ test_respects_throttle_limits_on_upload

#### AttentionAdminTest (18 tests)
- ✓ test_admin_can_list_attentions
- ✓ test_guest_cannot_access_admin_endpoints
- ✓ test_admin_can_view_detail
- ✓ test_admin_can_assign_to_department
- ✓ test_admin_can_assign_to_user
- ✓ test_admin_can_change_status
- ✓ test_admin_can_resolve
- ✓ test_admin_can_close
- ✓ test_admin_can_add_note
- ✓ test_admin_can_list_notes
- ✓ test_admin_can_view_actions_history
- ✓ test_admin_can_view_emails_history
- ✓ test_admin_can_view_stats
- ✓ test_admin_can_filter_by_status
- ✓ test_admin_can_search_attentions
- ✓ test_resolve_requires_resolution_text
- ✓ test_resolve_requires_response_type
- ✓ test_admin_can_update_attention_details

### Unit Tests

#### AttentionModelTest (36 tests)
- ✓ test_generates_uid_automatically
- ✓ test_generates_radicado
- ✓ test_generates_sequential_radicados
- ✓ test_can_add_document
- ✓ test_can_remove_document
- ✓ test_can_log_action
- ✓ test_can_add_note
- ✓ test_can_change_status
- ✓ test_can_assign_to_user
- ✓ test_can_assign_to_department
- ✓ test_can_resolve_attention
- ✓ test_can_close_attention
- ✓ test_scope_recent_orders_by_date
- ✓ test_scope_by_radicado
- ✓ test_scope_by_status
- ✓ test_scope_pending
- ✓ test_scope_in_process
- ✓ test_scope_resolved
- ✓ test_scope_closed
- ✓ test_scope_assigned_to
- ✓ test_scope_by_department
- ✓ test_scope_search
- ✓ test_can_check_if_can_be_edited
- ✓ test_can_check_if_is_resolved
- ✓ test_can_check_if_is_closed
- ✓ test_full_name_accessor_for_regular_user
- ✓ test_full_name_accessor_for_anonymous_user
- ✓ test_status_label_accessor
- ✓ test_names_are_converted_to_uppercase
- ✓ test_relationships_are_loaded_correctly

#### HasAttachmentsTraitTest (26 tests)
- ✓ test_can_add_document
- ✓ test_can_add_document_with_custom_name
- ✓ test_can_get_documents_array
- ✓ test_can_remove_document
- ✓ test_remove_document_returns_false_for_non_existent_id
- ✓ test_can_check_has_documents
- ✓ test_documents_count
- ✓ test_can_get_documents
- ✓ test_can_clear_documents
- ✓ test_can_get_first_document
- ✓ test_get_first_document_returns_null_when_no_documents
- ✓ test_can_get_first_document_url
- ✓ test_get_first_document_url_returns_null_when_no_documents
- ✓ test_can_check_has_specific_document
- ✓ test_can_get_specific_document_by_id
- ✓ test_get_document_returns_null_for_non_existent_id
- ✓ test_sanitizes_file_names
- ✓ test_documents_belong_to_correct_collection
- ✓ test_cannot_remove_document_from_different_model
- ✓ test_accepts_configured_mime_types
- ✓ test_respects_max_file_size_configuration
- ✓ test_documents_array_includes_human_readable_size
- ✓ test_documents_array_includes_creation_timestamp

**Total: 122 tests**

## Helpers Disponibles en TestCase

### Creación de Datos

```php
// Crear tipos, categorías, sedes, departamentos
$type = $this->createAttentionType(['code' => 'P']);
$category = $this->createCategory(['name' => 'Servicios']);
$sede = $this->createSede(['name' => 'Sede Principal']);
$department = $this->createDepartment(['name' => 'Atención']);

// Crear usuarios
$user = $this->createUser(['email' => 'user@example.com']);

// Crear PQRSF
$attention = $this->createAttention(['subject' => 'Mi solicitud']);
$anonymous = $this->createAnonymousAttention();

// Datos de prueba
$data = $this->validAttentionData(['subject' => 'Custom']);
$anonymousData = $this->validAnonymousAttentionData();
```

### Autenticación

```php
// Autenticar como usuario regular
$this->actingAsUser();

// Autenticar como settings
$this->actingAsAdmin();

// Con usuario específico
$user = $this->createUser();
$this->actingAsUser($user);
```

### Archivos de Prueba

```php
// Crear archivo PDF de prueba
$file = $this->createTestFile('documento.pdf', 1024); // 1MB

// Crear imagen de prueba
$image = $this->createTestImage('foto.jpg');
```

## Configuración de Tests

El archivo `phpunit.xml` está configurado con:

- Base de datos SQLite en memoria
- Cache, sesiones y colas en array
- Mail en array (no envía emails reales)
- Cobertura de código habilitada

## Troubleshooting

### Error: "Class not found"
```bash
composer dump-autoload
```

### Error: "Database tables not found"
```bash
php artisan migrate:fresh --env=testing
```

### Tests lentos
Los tests con throttling pueden ser lentos. Considera ejecutarlos por separado:
```bash
vendor/bin/phpunit --exclude-group throttle
```

### Storage fake issues
Si hay problemas con archivos, asegúrate de que el directorio `storage/` tenga permisos correctos:
```bash
chmod -R 775 storage/
```

## Contribuir

Al agregar nuevos tests:

1. Sigue la convención de nombres: `test_describe_what_it_does`
2. Usa el patrón AAA (Arrange, Act, Assert)
3. Agrega comentarios `// Arrange`, `// Act`, `// Assert`
4. Tests independientes (no dependan del orden)
5. Limpia los datos después de cada test (RefreshDatabase)

## Ejecutar Tests en CI/CD

```yaml
# Ejemplo para GitHub Actions
- name: Run tests
  run: |
    php artisan migrate --env=testing
    vendor/bin/phpunit modules/Attention/tests --coverage-clover coverage.xml
```

## Contacto

Para reportar issues o sugerencias sobre los tests, crear un issue en el repositorio.
