# Plan de Implementación: Mailer → Mailrelay

**Fecha**: 2026-01-25
**Objetivo**: Traer TODOS los componentes de Mailer a Mailrelay como sistema standalone
**Estado**: 📋 PLANIFICACIÓN COMPLETA

---

## 🎯 Resumen Ejecutivo

Implementar en Mailrelay un sistema completo de gestión de templates, layouts, variables y endpoints equivalente al módulo Mailer, pero integrado con la arquitectura de Mailrelay (usando Entities en vez de Models, integrando con EmailTemplate, etc.).

### Componentes a Implementar

| Componente | Mailer | Mailrelay Actual | Por Implementar |
|------------|--------|------------------|-----------------|
| **Models/Entities** | 9 modelos | 1 (EmailTemplate) | 8 nuevos |
| **Controllers** | 4 (2,173 líneas) | 1 (182 líneas) | 3 nuevos + fix 1 |
| **Services** | 4 servicios | 0 relacionados | 4 nuevos |
| **Routes** | 37 rutas | ~10 rutas template | 27+ nuevas |
| **Views** | 16 vistas | 4 vistas template | 12+ nuevas |
| **Migrations** | 9 migraciones | 0 relacionadas | 8+ nuevas |

---

## 🔧 Fase 1: Correcciones Críticas (30 min)

### 1.1 Fix TemplateController Bug ⚠️ CRÍTICO

**Archivo**: `modules/Mailrelay/app/Http/Controllers/Managers/TemplateController.php`

**Problema**:
```php
// Línea 10 - ROTO
use Modules\Mailrelay\Models\MailrelayTemplate;  // ❌ NO EXISTE
```

**Solución**:
```php
// CORRECCIÓN
use Modules\Mailrelay\Entities\EmailTemplate;
```

**Archivos a Modificar**:
- [ ] `TemplateController.php` - Cambiar todos los `MailrelayTemplate` por `EmailTemplate`
- [ ] Verificar métodos y relaciones
- [ ] Probar endpoints existentes

**Tiempo estimado**: 15 minutos

---

## 📦 Fase 2: Modelos y Migraciones (2-3 horas)

### 2.1 Sistema de Layouts/Components

#### Model: MailrelayLayout
**Archivo**: `modules/Mailrelay/app/Entities/MailrelayLayout.php`

**Características**:
- Tipos: `partial`, `layout`, `component`
- Multi-idioma con MailrelayLayoutLang
- Estado activo/inactivo
- Relación con EmailTemplate

**Campos**:
```php
protected $fillable = [
    'name',           // Nombre interno
    'type',           // partial|layout|component
    'content',        // HTML del layout
    'description',    // Descripción opcional
    'status',         // active|inactive
    'is_default',     // Layout por defecto
    'sort_order',     // Orden de visualización
];
```

**Relaciones**:
```php
public function translations() // hasMany MailrelayLayoutLang
public function templates()    // hasMany EmailTemplate (que usen este layout)
```

**Tiempo estimado**: 30 minutos

#### Model: MailrelayLayoutLang
**Archivo**: `modules/Mailrelay/app/Entities/MailrelayLayoutLang.php`

**Campos**:
```php
protected $fillable = [
    'mailrelay_layout_id',
    'lang_id',
    'name',              // Nombre traducido
    'description',       // Descripción traducida
];
```

**Tiempo estimado**: 15 minutos

#### Migration: create_mailrelay_layouts_table
**Archivo**: `modules/Mailrelay/database/migrations/2026_01_25_130000_create_mailrelay_layouts_table.php`

```sql
CREATE TABLE mailrelay_layouts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('partial', 'layout', 'component') DEFAULT 'layout',
    content TEXT,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    is_default BOOLEAN DEFAULT false,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE mailrelay_layout_langs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    mailrelay_layout_id BIGINT NOT NULL,
    lang_id BIGINT NOT NULL,
    name VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (mailrelay_layout_id) REFERENCES mailrelay_layouts(id) ON DELETE CASCADE
);
```

**Tiempo estimado**: 20 minutos

---

### 2.2 Sistema de Variables

#### Model: MailrelayVariable
**Archivo**: `modules/Mailrelay/app/Entities/MailrelayVariable.php`

**Características**:
- Categorías: `system`, `customer`, `order`, `document`, `custom`
- Módulos: `core`, `documents`, `orders`, `customers`, etc.
- Variables con placeholder {VARIABLE_NAME}
- Multi-idioma

**Campos**:
```php
protected $fillable = [
    'name',              // Nombre de la variable
    'variable_key',      // {VARIABLE_NAME}
    'category',          // system|customer|order|document|custom
    'module',            // core|documents|orders|customers
    'description',       // Descripción de la variable
    'example_value',     // Valor de ejemplo
    'status',            // active|inactive
    'is_system',         // Variable del sistema (no editable)
    'sort_order',
];
```

**Relaciones**:
```php
public function translations() // hasMany MailrelayVariableLang
```

**Métodos**:
```php
public function getPlaceholder(): string        // Retorna {VARIABLE_KEY}
public function getReplacementValue($context)   // Obtiene valor real según contexto
public static function getByCategory($category) // Filtrar por categoría
public static function getByModule($module)     // Filtrar por módulo
```

**Tiempo estimado**: 45 minutos

#### Model: MailrelayVariableLang
**Archivo**: `modules/Mailrelay/app/Entities/MailrelayVariableLang.php`

**Campos**:
```php
protected $fillable = [
    'mailrelay_variable_id',
    'lang_id',
    'name',
    'description',
    'example_value',
];
```

**Tiempo estimado**: 15 minutos

#### Migration: create_mailrelay_variables_table
**Archivo**: `modules/Mailrelay/database/migrations/2026_01_25_140000_create_mailrelay_variables_table.php`

```sql
CREATE TABLE mailrelay_variables (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    variable_key VARCHAR(255) NOT NULL UNIQUE,
    category ENUM('system', 'customer', 'order', 'document', 'custom') DEFAULT 'custom',
    module VARCHAR(100),
    description TEXT,
    example_value VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    is_system BOOLEAN DEFAULT false,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE mailrelay_variable_langs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    mailrelay_variable_id BIGINT NOT NULL,
    lang_id BIGINT NOT NULL,
    name VARCHAR(255),
    description TEXT,
    example_value VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (mailrelay_variable_id) REFERENCES mailrelay_variables(id) ON DELETE CASCADE
);
```

**Tiempo estimado**: 20 minutos

---

### 2.3 Sistema de Endpoints

#### Model: MailrelayEndpoint
**Archivo**: `modules/Mailrelay/app/Entities/MailrelayEndpoint.php`

**Características**:
- Endpoints HTTP para envío de emails
- Logging de llamadas
- Rate limiting
- Autenticación con API key

**Campos**:
```php
protected $fillable = [
    'name',
    'endpoint_key',      // Clave única para el endpoint
    'description',
    'template_id',       // EmailTemplate a usar
    'status',            // active|inactive
    'api_key',           // Encrypted
    'rate_limit',        // Requests por minuto
    'allowed_ips',       // JSON array de IPs permitidas
    'webhook_url',       // URL para callbacks
    'last_used_at',
];
```

**Relaciones**:
```php
public function template()  // belongsTo EmailTemplate
public function logs()      // hasMany MailrelayEndpointLog
```

**Tiempo estimado**: 30 minutos

#### Model: MailrelayEndpointLog
**Archivo**: `modules/Mailrelay/app/Entities/MailrelayEndpointLog.php`

**Campos**:
```php
protected $fillable = [
    'mailrelay_endpoint_id',
    'request_ip',
    'request_method',
    'request_payload',   // JSON
    'response_status',
    'response_body',
    'error_message',
    'executed_at',
];
```

**Tiempo estimado**: 15 minutos

#### Migration: create_mailrelay_endpoints_table
**Archivo**: `modules/Mailrelay/database/migrations/2026_01_25_150000_create_mailrelay_endpoints_table.php`

```sql
CREATE TABLE mailrelay_endpoints (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    endpoint_key VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    template_id BIGINT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    api_key VARCHAR(255),
    rate_limit INT DEFAULT 60,
    allowed_ips TEXT,
    webhook_url VARCHAR(500),
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL
);

CREATE TABLE mailrelay_endpoint_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    mailrelay_endpoint_id BIGINT NOT NULL,
    request_ip VARCHAR(45),
    request_method VARCHAR(10),
    request_payload TEXT,
    response_status INT,
    response_body TEXT,
    error_message TEXT,
    executed_at TIMESTAMP,
    FOREIGN KEY (mailrelay_endpoint_id) REFERENCES mailrelay_endpoints(id) ON DELETE CASCADE,
    INDEX idx_endpoint_executed (mailrelay_endpoint_id, executed_at)
);
```

**Tiempo estimado**: 25 minutos

---

### 2.4 Extensiones a EmailTemplate

**Archivo**: `modules/Mailrelay/app/Entities/EmailTemplate.php` (modificar)

**Agregar Relaciones**:
```php
public function layout()  // belongsTo MailrelayLayout
public function variables() // belongsToMany MailrelayVariable (tabla pivot)
```

**Agregar Campos a Migration**:
```php
// En migration de email_templates agregar:
$table->foreignId('layout_id')->nullable()->constrained('mailrelay_layouts')->nullOnDelete();
$table->boolean('use_layout')->default(true);
```

**Tiempo estimado**: 20 minutos

---

## 🎨 Fase 3: Controladores (4-5 horas)

### 3.1 TemplateController (Mejorado)

**Archivo**: `modules/Mailrelay/app/Http/Controllers/Managers/TemplateController.php`

**Métodos a Agregar** (siguiendo Mailer):
- [x] `index()` - Ya existe
- [x] `create()` - Ya existe
- [x] `store()` - Ya existe
- [x] `edit()` - Ya existe
- [x] `update()` - Ya existe
- [ ] `previewAjax($id)` - Vista previa en vivo sin guardar
- [ ] `variables($id)` - Lista de variables disponibles para el template
- [ ] `formatHtml(Request $request)` - Formatear HTML automáticamente
- [ ] `sendTest(Request $request, $id)` - Enviar email de prueba
- [ ] `toggleStatus($id)` - Activar/desactivar template
- [ ] `duplicate($id)` - Duplicar template

**Líneas**: De 182 a ~800 líneas

**Tiempo estimado**: 90 minutos

---

### 3.2 ComponentController (NUEVO)

**Archivo**: `modules/Mailrelay/app/Http/Controllers/Managers/ComponentController.php`

**Métodos** (equivalente a MailerComponentController):
```php
public function index()                          // Lista de layouts/components
public function create()                         // Formulario crear
public function store(Request $request)          // Guardar nuevo
public function edit($id)                        // Formulario editar
public function update(Request $request, $id)    // Actualizar
public function destroy($id)                     // Eliminar
public function previewAjax($id)                 // Vista previa AJAX
public function toggleStatus($id)                // Activar/desactivar
public function duplicate($id)                   // Duplicar layout
public function getByType($type)                 // API: filtrar por tipo
```

**Validación**:
```php
// StoreComponentRequest.php
'name' => 'required|max:255',
'type' => 'required|in:partial,layout,component',
'content' => 'required',
'status' => 'in:active,inactive',
```

**Líneas**: ~500-600 líneas

**Tiempo estimado**: 2 horas

---

### 3.3 VariableController (NUEVO)

**Archivo**: `modules/Mailrelay/app/Http/Controllers/Managers/VariableController.php`

**Métodos** (equivalente a MailerVariableController):
```php
public function index()                          // Lista de variables
public function create()                         // Formulario crear
public function store(Request $request)          // Guardar nueva
public function edit($id)                        // Formulario editar
public function update(Request $request, $id)    // Actualizar
public function destroy($id)                     // Eliminar
public function toggleStatus($id)                // Activar/desactivar
public function getByCategory($category)         // API: por categoría
public function getByModule($module)             // API: por módulo
public function getAll()                         // API: todas activas
```

**Validación**:
```php
// StoreVariableRequest.php
'name' => 'required|max:255',
'variable_key' => 'required|unique:mailrelay_variables|regex:/^[A-Z_]+$/',
'category' => 'required|in:system,customer,order,document,custom',
'module' => 'nullable|max:100',
```

**Líneas**: ~300-400 líneas

**Tiempo estimado**: 1.5 horas

---

### 3.4 EndpointController (NUEVO)

**Archivo**: `modules/Mailrelay/app/Http/Controllers/Managers/EndpointController.php`

**Métodos**:
```php
public function index()                          // Lista de endpoints
public function create()                         // Formulario crear
public function store(Request $request)          // Guardar nuevo
public function edit($id)                        // Formulario editar
public function update(Request $request, $id)    // Actualizar
public function destroy($id)                     // Eliminar
public function regenerateKey($id)               // Regenerar API key
public function logs($id)                        // Ver logs del endpoint
public function clearLogs($id)                   // Limpiar logs
public function toggleStatus($id)                // Activar/desactivar
```

**Líneas**: ~250-300 líneas

**Tiempo estimado**: 1 hora

---

## 🔧 Fase 4: Servicios (2-3 horas)

### 4.1 VariableReplacementService

**Archivo**: `modules/Mailrelay/app/Services/VariableReplacementService.php`

**Responsabilidad**: Reemplazar {VARIABLES} en templates con valores reales

**Métodos**:
```php
public function replaceVariables(string $content, array $context): string
public function getAvailableVariables(string $category = null): Collection
public function extractVariablesFromContent(string $content): array
public function validateVariables(array $variables): bool
```

**Ejemplo de Uso**:
```php
$service = new VariableReplacementService();
$html = "Hello {CUSTOMER_NAME}, your order {ORDER_ID} is ready!";
$context = [
    'customer' => $customer,
    'order' => $order,
];
$replaced = $service->replaceVariables($html, $context);
// "Hello John Doe, your order #12345 is ready!"
```

**Tiempo estimado**: 90 minutos

---

### 4.2 TemplateRendererService

**Archivo**: `modules/Mailrelay/app/Services/TemplateRendererService.php`

**Responsabilidad**: Renderizar template completo con layout + variables + HTML formatting

**Métodos**:
```php
public function render(EmailTemplate $template, array $context): string
public function renderWithLayout(EmailTemplate $template, MailrelayLayout $layout, array $context): string
public function beautifyHtml(string $html): string
public function inlineCss(string $html): string
```

**Integración con Existing**:
- Debe integrarse con `CampaignRendererService` existente
- Puede usar `MailerTemplateRendererService` de Mailer como referencia

**Tiempo estimado**: 60 minutos

---

### 4.3 VariableValueService

**Archivo**: `modules/Mailrelay/app/Services/VariableValueService.php`

**Responsabilidad**: Obtener valores reales de variables según contexto

**Métodos**:
```php
public function getValue(MailrelayVariable $variable, array $context)
public function getCustomerVariables(Customer $customer): array
public function getOrderVariables(Order $order): array
public function getSystemVariables(): array
```

**Tiempo estimado**: 45 minutos

---

### 4.4 EndpointExecutionService

**Archivo**: `modules/Mailrelay/app/Services/EndpointExecutionService.php`

**Responsabilidad**: Ejecutar endpoints, validar API keys, rate limiting, logging

**Métodos**:
```php
public function execute(MailrelayEndpoint $endpoint, Request $request): Response
public function validateApiKey(string $apiKey, MailrelayEndpoint $endpoint): bool
public function checkRateLimit(MailrelayEndpoint $endpoint, string $ip): bool
public function logRequest(MailrelayEndpoint $endpoint, Request $request, Response $response): void
```

**Tiempo estimado**: 60 minutos

---

## 🛣️ Fase 5: Rutas (30 min)

**Archivo**: `modules/Mailrelay/routes/managers.php`

### Templates (13 rutas)
```php
Route::prefix('templates')->name('templates.')->group(function () {
    Route::get('/', [TemplateController::class, 'index'])->name('index');
    Route::get('/create', [TemplateController::class, 'create'])->name('create');
    Route::post('/', [TemplateController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [TemplateController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TemplateController::class, 'update'])->name('update');
    Route::delete('/{id}', [TemplateController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/preview', [TemplateController::class, 'preview'])->name('preview');
    Route::post('/{id}/preview-ajax', [TemplateController::class, 'previewAjax'])->name('preview-ajax');
    Route::get('/{id}/variables', [TemplateController::class, 'variables'])->name('variables');
    Route::post('/format-html', [TemplateController::class, 'formatHtml'])->name('format-html');
    Route::post('/{id}/send-test', [TemplateController::class, 'sendTest'])->name('send-test');
    Route::patch('/{id}/toggle-status', [TemplateController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{id}/duplicate', [TemplateController::class, 'duplicate'])->name('duplicate');
});
```

### Components/Layouts (9 rutas)
```php
Route::prefix('components')->name('components.')->group(function () {
    Route::get('/', [ComponentController::class, 'index'])->name('index');
    Route::get('/create', [ComponentController::class, 'create'])->name('create');
    Route::post('/', [ComponentController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ComponentController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ComponentController::class, 'update'])->name('update');
    Route::delete('/{id}', [ComponentController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/preview-ajax', [ComponentController::class, 'previewAjax'])->name('preview-ajax');
    Route::patch('/{id}/toggle-status', [ComponentController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{id}/duplicate', [ComponentController::class, 'duplicate'])->name('duplicate');
});
```

### Variables (10 rutas)
```php
Route::prefix('variables')->name('variables.')->group(function () {
    Route::get('/', [VariableController::class, 'index'])->name('index');
    Route::get('/create', [VariableController::class, 'create'])->name('create');
    Route::post('/', [VariableController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [VariableController::class, 'edit'])->name('edit');
    Route::put('/{id}', [VariableController::class, 'update'])->name('update');
    Route::delete('/{id}', [VariableController::class, 'destroy'])->name('destroy');
    Route::patch('/{id}/toggle-status', [VariableController::class, 'toggleStatus'])->name('toggle-status');
    Route::get('/by-category/{category}', [VariableController::class, 'getByCategory'])->name('by-category');
    Route::get('/by-module/{module}', [VariableController::class, 'getByModule'])->name('by-module');
    Route::get('/all', [VariableController::class, 'getAll'])->name('all');
});
```

### Endpoints (10 rutas)
```php
Route::prefix('endpoints')->name('endpoints.')->group(function () {
    Route::get('/', [EndpointController::class, 'index'])->name('index');
    Route::get('/create', [EndpointController::class, 'create'])->name('create');
    Route::post('/', [EndpointController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [EndpointController::class, 'edit'])->name('edit');
    Route::put('/{id}', [EndpointController::class, 'update'])->name('update');
    Route::delete('/{id}', [EndpointController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/regenerate-key', [EndpointController::class, 'regenerateKey'])->name('regenerate-key');
    Route::get('/{id}/logs', [EndpointController::class, 'logs'])->name('logs');
    Route::delete('/{id}/logs', [EndpointController::class, 'clearLogs'])->name('clear-logs');
    Route::patch('/{id}/toggle-status', [EndpointController::class, 'toggleStatus'])->name('toggle-status');
});
```

**Total**: 42 rutas nuevas

**Tiempo estimado**: 30 minutos

---

## 🎨 Fase 6: Vistas (3-4 horas)

### Templates Views (ya existen 4, mejorar)
- [x] `templates/index.blade.php` - Mejorar con filtros y búsqueda
- [x] `templates/create.blade.php` - Agregar selector de layout
- [x] `templates/edit.blade.php` - Agregar preview en vivo
- [ ] `templates/preview.blade.php` - Vista previa fullscreen
- [ ] `templates/variables.blade.php` - Modal con lista de variables

**Tiempo estimado**: 1 hora

### Components Views (NUEVAS)
- [ ] `components/index.blade.php` - Lista con filtro por tipo
- [ ] `components/create.blade.php` - Formulario con editor HTML
- [ ] `components/edit.blade.php` - Formulario edición
- [ ] `components/_form.blade.php` - Partial del formulario

**Tiempo estimado**: 90 minutos

### Variables Views (NUEVAS)
- [ ] `variables/index.blade.php` - Lista con filtros por categoría/módulo
- [ ] `variables/create.blade.php` - Formulario crear
- [ ] `variables/edit.blade.php` - Formulario editar
- [ ] `variables/_form.blade.php` - Partial del formulario

**Tiempo estimado**: 90 minutos

### Endpoints Views (NUEVAS)
- [ ] `endpoints/index.blade.php` - Lista de endpoints
- [ ] `endpoints/create.blade.php` - Formulario crear
- [ ] `endpoints/edit.blade.php` - Formulario editar
- [ ] `endpoints/logs.blade.php` - Tabla de logs
- [ ] `endpoints/_form.blade.php` - Partial del formulario

**Tiempo estimado**: 90 minutos

---

## 🧪 Fase 7: Testing (2-3 horas)

### Feature Tests
- [ ] `TemplateManagementTest.php` - CRUD, preview, send test
- [ ] `ComponentManagementTest.php` - CRUD layouts/components
- [ ] `VariableManagementTest.php` - CRUD variables
- [ ] `EndpointManagementTest.php` - CRUD endpoints
- [ ] `VariableReplacementTest.php` - Reemplazo de variables
- [ ] `TemplateRenderingTest.php` - Renderizado con layout

**Tiempo estimado**: 2 horas

### Unit Tests
- [ ] `VariableReplacementServiceTest.php`
- [ ] `TemplateRendererServiceTest.php`
- [ ] `EndpointExecutionServiceTest.php`

**Tiempo estimado**: 1 hora

---

## 📚 Fase 8: Documentación (1 hora)

- [ ] `TEMPLATES-SYSTEM.md` - Documentación del sistema de templates
- [ ] `VARIABLES-GUIDE.md` - Guía de uso de variables
- [ ] `ENDPOINTS-API.md` - Documentación de API endpoints
- [ ] Actualizar `README.md` con nuevas features

---

## 📊 Resumen de Tiempo Estimado

| Fase | Tiempo |
|------|--------|
| Fase 1: Correcciones Críticas | 30 min |
| Fase 2: Modelos y Migraciones | 2-3 horas |
| Fase 3: Controladores | 4-5 horas |
| Fase 4: Servicios | 2-3 horas |
| Fase 5: Rutas | 30 min |
| Fase 6: Vistas | 3-4 horas |
| Fase 7: Testing | 2-3 horas |
| Fase 8: Documentación | 1 hora |
| **TOTAL** | **15-20 horas** |

---

## ✅ Checklist de Implementación

### Modelos/Entities
- [ ] MailrelayLayout
- [ ] MailrelayLayoutLang
- [ ] MailrelayVariable
- [ ] MailrelayVariableLang
- [ ] MailrelayEndpoint
- [ ] MailrelayEndpointLog
- [ ] Extender EmailTemplate con relaciones

### Migraciones
- [ ] create_mailrelay_layouts_table
- [ ] create_mailrelay_layout_langs_table
- [ ] create_mailrelay_variables_table
- [ ] create_mailrelay_variable_langs_table
- [ ] create_mailrelay_endpoints_table
- [ ] create_mailrelay_endpoint_logs_table
- [ ] add_layout_to_email_templates

### Controladores
- [ ] Fix TemplateController (importar EmailTemplate correcto)
- [ ] Mejorar TemplateController con nuevos métodos
- [ ] ComponentController (nuevo)
- [ ] VariableController (nuevo)
- [ ] EndpointController (nuevo)

### Services
- [ ] VariableReplacementService
- [ ] TemplateRendererService
- [ ] VariableValueService
- [ ] EndpointExecutionService

### Rutas
- [ ] 13 rutas de templates
- [ ] 9 rutas de components
- [ ] 10 rutas de variables
- [ ] 10 rutas de endpoints

### Vistas
- [ ] Mejorar vistas de templates (4)
- [ ] Crear vistas de components (4)
- [ ] Crear vistas de variables (4)
- [ ] Crear vistas de endpoints (5)

### Testing
- [ ] 6 Feature Tests
- [ ] 3 Unit Tests

### Documentación
- [ ] TEMPLATES-SYSTEM.md
- [ ] VARIABLES-GUIDE.md
- [ ] ENDPOINTS-API.md
- [ ] Actualizar README.md

---

## 🚀 Orden de Implementación Recomendado

1. **Fase 1** - Fix críticos (bloquea todo)
2. **Fase 2.2** - Sistema de Variables (base para templates)
3. **Fase 2.1** - Sistema de Layouts (base para templates)
4. **Fase 4.1** - VariableReplacementService (necesario para rendering)
5. **Fase 4.2** - TemplateRendererService (rendering completo)
6. **Fase 3.1** - Mejorar TemplateController
7. **Fase 3.3** - VariableController
8. **Fase 3.2** - ComponentController
9. **Fase 2.3** - Sistema de Endpoints
10. **Fase 3.4** - EndpointController
11. **Fase 4.3, 4.4** - Servicios adicionales
12. **Fase 5** - Rutas
13. **Fase 6** - Vistas
14. **Fase 7** - Testing
15. **Fase 8** - Documentación

---

## 🎯 Objetivo Final

Un módulo Mailrelay completamente autónomo con:

✅ Sistema completo de gestión de templates
✅ Sistema de layouts/components reutilizables
✅ Sistema de variables dinámicas con categorías
✅ Endpoints HTTP para envío de emails
✅ Preview en vivo de templates
✅ Envío de emails de prueba
✅ Logging completo de endpoints
✅ Integración con sistema de permisos
✅ Multi-idioma en todos los módulos
✅ Tests completos (feature + unit)
✅ Documentación exhaustiva

**Resultado**: Sistema 100% equivalente a Mailer pero integrado en Mailrelay como módulo standalone.

---

**Creado por**: Claude Assistant
**Fecha**: 2026-01-25
**Basado en**: Análisis completo de módulos Mailer y Mailrelay
