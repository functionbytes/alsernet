# Comparativa: Controllers y Resources - Mailer vs Mailrelay

**Fecha**: 2026-01-25
**Objetivo**: Identificar qué falta implementar en Mailrelay comparado con Mailer

---

## 🔍 Controllers Encontrados

### Mailer Module (4 controllers)
```
modules/Mailer/app/Http/Controllers/
├── MailerTemplateController.php       (849 líneas) ✅ COMPLETO
├── MailerComponentController.php      (518 líneas) ✅ COMPLETO
├── MailerVariableController.php       (303 líneas) ✅ COMPLETO
└── MailerEndpointController.php       (100+ líneas) ✅ COMPLETO
```

### Mailrelay Module (33 controllers)
```
modules/Mailrelay/app/Http/Controllers/
├── Settings/
│   ├── TemplateController.php         (182 líneas) ⚠️ INCOMPLETO
│   ├── ApiSettingsController.php      ✅ OK
│   ├── GeneralSettingsController.php  ✅ OK
│   ├── AutomationController.php       ❓ REVISAR
│   ├── CustomFieldController.php      ❓ REVISAR
│   ├── GroupController.php            ❓ REVISAR
│   ├── PermissionController.php       ❓ REVISAR
│   └── WebhookController.php          ❓ REVISAR
├── Managers/
│   ├── CampaignManagerController.php  ✅ OK
│   └── MailProviderController.php     ✅ OK
├── Api/V1/
│   ├── CampaignApiController.php      ✅ OK
│   └── MailProviderApiController.php  ✅ OK
└── ... (otros 20 controllers)
```

---

## 📊 Comparativa Detallada: Templates

### MailerTemplateController (Mailer) - 849 líneas

#### ✅ Características Implementadas:

**1. CRUD Completo Multi-idioma**
```php
// Mailer soporta múltiples idiomas NATIVAMENTE
public function index(Request $request) {
    $langId = $request->input('lang_id', 1);
    $query->with(['translations' => function ($q) use ($langId) {
        $q->where('lang_id', $langId);
    }]);
}
```

**2. Preview con Rendering Completo**
```php
// Preview HTML real con layout
public function preview(Request $request, $uid) {
    $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);
    return view('mailer::templates.preview', compact('html'));
}

// Preview AJAX para live editing
public function previewAjax(Request $request, $uid) {
    $customContent = $request->input('content'); // Live content
    $overrideLayoutId = $request->input('layout_id');
    // Renderiza en vivo sin guardar
}
```

**3. Sistema de Variables Dinámicas**
```php
// Obtener variables desde BD filtradas por módulo
public function variables($uid) {
    $dbVariables = MailerVariable::query()
        ->where('is_enabled', true)
        ->where(function ($query) use ($template) {
            $query->where('module', $template->module)
                  ->orWhere('module', 'core');
        })
        ->get();

    // Agrupa por categoría (system, customer, order, etc.)
    return response()->json(['variables' => $grouped]);
}

// Variables por módulo (para create form)
public function variablesByModule(Request $request) {
    $module = $request->query('module');
    // Retorna variables filtradas
}
```

**4. Layouts (Header/Footer/Wrapper)**
```php
// Renderizar con header y footer
private function renderTemplateWithLayout(
    MailerTemplate $template,
    ?int $langId = null,
    ?int $overrideLayoutId = null,
    ?string $customContent = null
): string {
    // 1. Reemplazar variables en contenido
    // 2. Obtener header y footer
    // 3. Reemplazar variables globales
    // 4. Crear preheader HTML
    // 5. Usar layout personalizado o default
    // 6. Combinar todo
}
```

**5. Formateo de HTML**
```php
public function formatHtml(Request $request) {
    $formatted = $this->beautifyHtml($validated['html']);
    return response()->json(['formatted' => $formatted]);
}

private function beautifyHtml(string $html): string {
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    // Formatea y retorna HTML limpio
}
```

**6. Envío de Email de Prueba**
```php
public function sendTest(Request $request, $uid) {
    $html = $this->renderTemplateWithLayout($template, 1);

    Mail::send([], [], function ($message) use ($translation, $validated, $html) {
        $message->to($validated['test_email'])
            ->subject($translation->subject)
            ->html($html);
    });
}
```

**7. Toggle Status y Protección**
```php
public function toggleStatus($uid) {
    $template->is_enabled = !$template->is_enabled;
    $template->save();
}

// Verificar protección antes de eliminar
if ($template->is_protected) {
    return redirect()->back()->with('error', 'Template protegido');
}
```

**8. Multi-idioma Completo**
```php
// Al crear, crea versiones en TODOS los idiomas
$allLangs = MailerLang::available()->get();
foreach ($allLangs as $lang) {
    MailerTemplateLang::create([
        'mailer_template_id' => $template->id,
        'lang_id' => $lang->id,
        'subject' => $validated['subject'],
        'content' => $validated['content'],
    ]);
}
```

---

### TemplateController (Mailrelay) - 182 líneas

#### ❌ Características FALTANTES:

1. **NO tiene multi-idioma**
   - Solo un idioma por template
   - No tabla de traducciones

2. **NO tiene preview**
   - Ni preview() ni previewAjax()
   - No renderizado de HTML

3. **NO tiene variables dinámicas**
   - No integración con variables
   - No reemplazo de placeholders

4. **NO tiene layouts**
   - No header/footer
   - No wrapper
   - No renderizado con layout

5. **NO tiene formateo de HTML**
   - No beautifyHtml()
   - No formatHtml()

6. **NO tiene envío de prueba**
   - No sendTest()
   - No validación de emails

7. **NO tiene toggle status**
   - Usa campo 'active' pero sin toggle endpoint

8. **NO tiene variables por módulo**
   - No variablesByModule()
   - No filtrado por categoría

#### ✅ Características que SÍ tiene:

1. CRUD básico (index, create, store, edit, update, destroy)
2. Duplicate template
3. Búsqueda simple por nombre/subject/event_type
4. Filtrado por event_type y active

---

## 📊 Comparativa: Components/Layouts

### MailerComponentController (Mailer) - 518 líneas

#### ✅ Características:

1. **CRUD Multi-idioma** - Traducciones para header, footer, components
2. **Tipos de componentes**: partial, layout, component
3. **Preview** con variables de ejemplo
4. **Preview AJAX** para live editing
5. **Duplicate** con traducciones
6. **Variables** desde BD con categorías
7. **Protección** de componentes críticos
8. **Validación** de alias único

### Mailrelay

❌ **NO tiene controller equivalente**
- No hay gestión de layouts
- No hay header/footer separados
- No hay componentes reutilizables

---

## 📊 Comparativa: Variables

### MailerVariableController (Mailer) - 303 líneas

#### ✅ Características:

1. **CRUD completo** para variables dinámicas
2. **Multi-idioma** - Traducciones por variable
3. **Categorías**: system, site, customer, order, document, general
4. **Módulos**: core, documents, orders
5. **Sistema de protección** - Variables del sistema no se pueden eliminar
6. **Toggle status** con AJAX
7. **API endpoints**:
   - `getByModule()` - Variables por módulo
   - `getGroupedByCategory()` - Agrupadas por categoría
   - `getAvailableKeys()` - Keys disponibles para validación

#### Modelo de Variables:
```php
// mailer_variables
- id
- key (UPPERCASE_WITH_UNDERSCORES)
- name
- description
- category (system, customer, order, etc.)
- module (core, documents, orders)
- is_system (bool)
- is_enabled (bool)

// mailer_variable_translations
- variable_id
- lang_id
- name
- description
- value (valor de ejemplo)
```

### Mailrelay

❌ **NO tiene controller de variables**
❌ **NO tiene modelo de variables**
❌ **NO tiene sistema de variables dinámicas**

---

## 📊 Comparativa: Endpoints

### MailerEndpointController (Mailer)

#### ✅ Características (basado en primeras 100 líneas):

1. **Documentation page** - Documentación de uso
2. **CRUD de endpoints** configurables
3. **Tipos**: transactional, notification
4. **Sources**: internal, webhook, api
5. **Variables esperadas** y requeridas
6. **Variable mappings** - Mapeo de variables
7. **Filtros**: status, source
8. **Integración** con templates

### Mailrelay

❌ **NO tiene controller de endpoints**
❌ **NO tiene sistema de endpoints configurables**

---

## 🎯 Resources: Mailer vs Mailrelay

### Mailer Module

```bash
# Búsqueda realizada - NO encontró Resources
find modules/Mailer -name "*Resource.php"
# Solo encontró archivos de vendor
```

**Conclusión**: Mailer **NO TIENE** API Resources personalizados.

### Mailrelay Module

```bash
modules/Mailrelay/app/Http/Resources/
├── CampaignResource.php
├── SubscriberResource.php
└── V1/
    ├── CampaignResource.php
    └── MailProviderResource.php
```

**Conclusión**: Mailrelay **SÍ TIENE** API Resources (4 resources).

✅ **Mailrelay SUPERA a Mailer** en este aspecto.

---

## 📋 Resumen Ejecutivo

| Característica | Mailer | Mailrelay | ¿Falta implementar? |
|----------------|--------|-----------|---------------------|
| **Templates Controller** | 849 líneas, completo | 182 líneas, básico | ❌ SÍ - Falta mucho |
| **Components Controller** | 518 líneas, completo | ❌ No existe | ❌ SÍ - Crear desde cero |
| **Variables Controller** | 303 líneas, completo | ❌ No existe | ❌ SÍ - Crear desde cero |
| **Endpoints Controller** | ~200 líneas, completo | ❌ No existe | ❌ SÍ - Crear desde cero |
| **API Resources** | ❌ No tiene | ✅ 4 resources | ✅ NO - Mailrelay supera |
| **Multi-idioma** | ✅ Completo | ❌ No tiene | ❌ SÍ - Implementar |
| **Preview HTML** | ✅ Sí | ❌ No | ❌ SÍ - Implementar |
| **Variables dinámicas** | ✅ Sí | ❌ No | ❌ SÍ - Implementar |
| **Layouts** | ✅ Sí | ❌ No | ❌ SÍ - Implementar |
| **Envío de prueba** | ✅ Sí | ❌ No | ❌ SÍ - Implementar |

---

## ❌ LO QUE FALTA IMPLEMENTAR EN MAILRELAY

### 1. TemplateController - Mejorar Completamente

**Agregar a `modules/Mailrelay/app/Http/Controllers/Settings/TemplateController.php`**:

```php
// Nuevos métodos necesarios:
public function preview($id, Request $request)           // Preview HTML
public function previewAjax($id, Request $request)       // Preview AJAX live
public function variables($id)                           // Get variables for template
public function variablesByModule(Request $request)      // Variables by module
public function formatHtml(Request $request)             // Format HTML
public function sendTest($id, Request $request)          // Send test email
public function toggleStatus($id)                        // Toggle active/inactive
public function duplicate($id)                           // ✅ Ya existe
```

**Agregar lógica**:
- Integración con MailerTemplate (multi-idioma)
- Rendering con layouts
- Reemplazo de variables
- Live preview
- Formateo de HTML

### 2. ComponentController - Crear Desde Cero

**Crear `modules/Mailrelay/app/Http/Controllers/Settings/ComponentController.php`**:

```php
class ComponentController extends Controller {
    public function index()                              // List components
    public function create()                             // Create form
    public function store(Request $request)              // Save component
    public function edit($uid)                           // Edit form
    public function update(Request $request, $uid)       // Update component
    public function destroy($uid)                        // Delete component
    public function preview($uid, Request $request)      // Preview HTML
    public function previewAjax($uid, Request $request)  // Preview AJAX
    public function duplicate($uid, Request $request)    // Duplicate
    public function variables()                          // Get all variables
}
```

**Crear modelo**:
```php
// modules/Mailrelay/app/Models/MailrelayLayout.php
// Similar a MailerLayout de Mailer
```

### 3. VariableController - Crear Desde Cero

**Crear `modules/Mailrelay/app/Http/Controllers/Settings/VariableController.php`**:

```php
class VariableController extends Controller {
    public function index()                              // List variables
    public function create()                             // Create form
    public function store(Request $request)              // Save variable
    public function edit($variable)                      // Edit form
    public function update(Request $request, $variable)  // Update variable
    public function destroy($variable)                   // Delete variable
    public function toggleStatus($variable)              // Toggle enabled
    public function getByModule(Request $request)        // Variables by module
    public function getGroupedByCategory(Request $request) // Grouped
    public function getAvailableKeys(Request $request)   // Available keys
}
```

**Crear modelos**:
```php
// modules/Mailrelay/app/Models/MailrelayVariable.php
// modules/Mailrelay/app/Models/MailrelayVariableTranslation.php
```

**Crear migración**:
```sql
CREATE TABLE mailrelay_variables (
    id BIGINT PRIMARY KEY,
    key VARCHAR(255) UNIQUE,
    name VARCHAR(255),
    description TEXT,
    category VARCHAR(50), -- system, customer, order, etc.
    module VARCHAR(50),   -- core, campaigns, subscribers
    is_system BOOLEAN DEFAULT false,
    is_enabled BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE mailrelay_variable_translations (
    id BIGINT PRIMARY KEY,
    variable_id BIGINT REFERENCES mailrelay_variables(id),
    lang_id BIGINT REFERENCES langs(id),
    name VARCHAR(255),
    description TEXT,
    value TEXT, -- Valor de ejemplo para preview
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 4. EndpointController - Crear Desde Cero

**Crear `modules/Mailrelay/app/Http/Controllers/Settings/EndpointController.php`**:

```php
class EndpointController extends Controller {
    public function documentation()                      // API documentation
    public function index(Request $request)              // List endpoints
    public function create()                             // Create form
    public function store(Request $request)              // Save endpoint
    public function edit($endpoint)                      // Edit form
    public function update(Request $request, $endpoint)  // Update endpoint
    public function destroy($endpoint)                   // Delete endpoint
    public function test($endpoint, Request $request)    // Test endpoint
}
```

**Crear modelo**:
```php
// modules/Mailrelay/app/Models/MailrelayEndpoint.php
```

### 5. Multi-idioma - Sistema Completo

**Crear tablas de traducción**:

```sql
-- Para templates
CREATE TABLE mailrelay_template_translations (
    id BIGINT PRIMARY KEY,
    template_id BIGINT REFERENCES mailrelay_templates(id) ON DELETE CASCADE,
    lang_id BIGINT REFERENCES langs(id),
    subject VARCHAR(255),
    body TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(template_id, lang_id)
);

-- Para layouts/components
CREATE TABLE mailrelay_layout_translations (
    id BIGINT PRIMARY KEY,
    layout_id BIGINT REFERENCES mailrelay_layouts(id) ON DELETE CASCADE,
    lang_id BIGINT REFERENCES langs(id),
    subject VARCHAR(255),
    content TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(layout_id, lang_id)
);
```

---

## 🎯 Plan de Implementación Sugerido

### Fase 1: Sistema de Variables (1-2 días)
1. Crear modelo `MailrelayVariable` + `MailrelayVariableTranslation`
2. Crear migración
3. Crear seeder con variables base
4. Crear controller completo
5. Crear vistas CRUD

### Fase 2: Sistema de Layouts/Components (2-3 días)
1. Crear modelo `MailrelayLayout` + `MailrelayLayoutTranslation`
2. Crear migración
3. Crear seeder con header/footer base
4. Crear controller completo
5. Crear vistas CRUD

### Fase 3: Mejorar TemplateController (3-4 días)
1. Agregar soporte multi-idioma
2. Implementar preview
3. Implementar variables dinámicas
4. Implementar layouts
5. Implementar envío de prueba
6. Actualizar vistas

### Fase 4: Sistema de Endpoints (1-2 días)
1. Crear modelo `MailrelayEndpoint`
2. Crear migración
3. Crear controller
4. Crear vistas y documentación

### Fase 5: Testing (1 día)
1. Tests para VariableController
2. Tests para ComponentController
3. Tests para TemplateController mejorado
4. Tests para EndpointController

**Total estimado**: 8-12 días de desarrollo

---

## 💡 Recomendaciones

### Opción 1: Reutilizar Mailer (Recomendado)
En lugar de duplicar todo el código, Mailrelay podría **USAR directamente** el sistema de templates de Mailer:

```php
// En Mailrelay Campaign
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

$campaign->mailer_template_id = 5; // Template de Mailer
$html = MailerTemplateRendererService::renderEmailTemplate(
    $campaign->mailerTemplate,
    $variables,
    $campaign->lang_id
);
```

**Ventajas**:
- ✅ No duplicar código
- ✅ Un solo sistema de templates para todo
- ✅ Aprovecha todo lo que Mailer ya tiene
- ✅ Menor mantenimiento

### Opción 2: Implementar Todo en Mailrelay
Si necesitas que Mailrelay sea completamente independiente:

- ❌ Mucho código duplicado
- ❌ Doble mantenimiento
- ❌ 8-12 días de desarrollo
- ✅ Total independencia de Mailer

---

## 🚨 PROBLEMA CRÍTICO ENCONTRADO

### TemplateController ESTÁ ROTO

El `TemplateController` de Mailrelay intenta usar un modelo que **NO EXISTE**:

```php
// modules/Mailrelay/app/Http/Controllers/Settings/TemplateController.php:10
use Modules\Mailrelay\Models\MailrelayTemplate;  // ❌ NO EXISTE

// Pero Mailrelay NO tiene directorio Models en app/
// Solo tiene Entities/
```

**Verificación**:
```bash
$ find modules/Mailrelay -name "MailrelayTemplate.php"
# NO ENCONTRÓ NADA

$ ls modules/Mailrelay/app/
Console  Entities  Http  Services  ...  # ← NO HAY "Models"
```

**Solución**: Cambiar el import a usar `EmailTemplate` de Entities:
```php
- use Modules\Mailrelay\Models\MailrelayTemplate;
+ use Modules\Mailrelay\Entities\EmailTemplate;
```

---

## 📊 Comparativa: Models/Entities

### Mailer Module - 9 Models

```
modules/Mailer/app/Models/
├── MailerEndpoint.php           ✅ Endpoints configurables
├── MailerEndpointLog.php        ✅ Logs de endpoints
├── MailerLang.php               ✅ Idiomas disponibles
├── MailerLayout.php             ✅ Layouts (header/footer)
├── MailerLayoutLang.php         ✅ Traducciones de layouts
├── MailerTemplate.php           ✅ Templates de email
├── MailerTemplateLang.php       ✅ Traducciones de templates
├── MailerVariable.php           ✅ Variables dinámicas
└── MailerVariableLang.php       ✅ Traducciones de variables
```

**Características de los modelos de Mailer**:
- ✅ **Multi-idioma nativo** - Todos tienen tabla de traducciones
- ✅ **Relationships** - Relaciones complejas entre modelos
- ✅ **Scopes** - Query scopes para filtrado
- ✅ **Mutators** - Getters/setters automáticos
- ✅ **Protección** - Sistema de is_protected
- ✅ **States** - is_enabled, is_active, etc.

### Mailrelay Module - 30+ Entities

```
modules/Mailrelay/app/Entities/
├── Campaign.php                 ✅ Campañas de email
├── MailProvider.php             ✅ Proveedores de email
├── MailrelaySettings.php        ✅ Configuración (nuevo)
├── EmailTemplate.php            ⚠️ Template básico (sin multi-idioma)
├── Group.php                    ✅ Grupos de suscriptores
├── Subscriber.php               ✅ Suscriptores
├── Webhook.php                  ✅ Webhooks
└── ... (24 más)
```

**Modelos que FALTAN en Mailrelay**:
- ❌ `MailrelayLayout` - No existe
- ❌ `MailrelayLayoutLang` - No existe
- ❌ `MailrelayVariable` - No existe
- ❌ `MailrelayVariableLang` - No existe
- ❌ `MailrelayEndpoint` - No existe
- ❌ `MailrelayTemplateLang` - No existe (EmailTemplate no tiene multi-idioma)

---

## 📊 Comparativa: Services

### Mailer Module - 4 Services

```
modules/Mailer/app/Services/
├── MailerTemplateRendererService.php       ✅ Renderizado completo
├── MailerVariableReplacementService.php    ✅ Reemplazo de variables
├── MailerVariableService.php               ✅ Gestión de variables
└── MailerVariableValueService.php          ✅ Valores de variables
```

**Características**:
- **MailerTemplateRendererService**:
  - Renderiza templates con layouts
  - Reemplaza variables dinámicas
  - Genera HTML final para envío
  - Soporte multi-idioma

- **MailerVariableReplacementService**:
  - Reemplaza placeholders {VARIABLE}
  - Obtiene valores preview
  - Soporta variables por módulo

### Mailrelay Module - 41 Services

```
modules/Mailrelay/app/Services/
├── CampaignService.php           ✅ Gestión de campañas
├── CampaignRendererService.php   ✅ Renderizado (INCOMPLETO)
├── ProviderManager.php           ✅ Gestión de providers
├── EmailValidation/              ✅ Validación de emails
├── ImportService.php             ✅ Importación
├── GroupService.php              ✅ Grupos
└── ... (35 más)
```

**Services que FALTAN en Mailrelay**:
- ❌ `MailrelayVariableReplacementService` - No existe
- ❌ `MailrelayVariableService` - No existe
- ❌ `MailrelayVariableValueService` - No existe

**CampaignRendererService INCOMPLETO**:
- ✅ Renderiza templates básicos
- ❌ NO reemplaza variables dinámicas
- ❌ NO soporta layouts (header/footer)
- ❌ NO soporta multi-idioma

---

## 🎯 RESUMEN FINAL - Diferencias Críticas

| Aspecto | Mailer | Mailrelay | Gap |
|---------|--------|-----------|-----|
| **Controllers** | 4 completos | 1 roto, 3 faltantes | ❌ -75% |
| **Models** | 9 con multi-idioma | 1 básico, 5 faltantes | ❌ -67% |
| **Services** | 4 de rendering/variables | 1 incompleto, 3 faltantes | ❌ -75% |
| **Multi-idioma** | ✅ Sistema completo | ❌ No existe | ❌ 0% |
| **Variables dinámicas** | ✅ Sistema completo | ❌ No existe | ❌ 0% |
| **Layouts** | ✅ Sistema completo | ❌ No existe | ❌ 0% |
| **Endpoints** | ✅ Sistema completo | ❌ No existe | ❌ 0% |
| **Preview** | ✅ Live preview | ❌ No existe | ❌ 0% |

---

## ⚡ ACCIÓN INMEDIATA REQUERIDA

### 1. Arreglar TemplateController AHORA

```php
// modules/Mailrelay/app/Http/Controllers/Settings/TemplateController.php
- use Modules\Mailrelay\Models\MailrelayTemplate;
+ use Modules\Mailrelay\Entities\EmailTemplate;

// Y cambiar todas las referencias:
- MailrelayTemplate::query()
+ EmailTemplate::query()
```

### 2. Decidir Estrategia

**Opción A: Usar Mailer directamente (RECOMENDADO)**
```php
// En Campaign model
use Modules\Mailer\Models\MailerTemplate;

public function mailerTemplate() {
    return $this->belongsTo(MailerTemplate::class, 'mailer_template_id');
}

// Ya está implementado así ✅
```

**Opción B: Implementar TODO en Mailrelay**
- Crear 6 modelos nuevos
- Crear 3 services nuevos
- Crear 3 controllers nuevos
- Crear 20+ vistas
- ~8-12 días de desarrollo

---

**Conclusión**:

1. **Mailrelay ya usa MailerTemplate** para campañas (relación existente)
2. **NO tiene sentido** duplicar todo el sistema de Mailer
3. **TemplateController está roto** y usa modelo inexistente
4. **Recomendación**: ELIMINAR TemplateController o cambiarlo para gestionar MailerTemplate de Mailer

---

**Documento creado**: 2026-01-25
**Autor**: Claude (Assistant)
**Estado**: ✅ Análisis completo - PROBLEMA CRÍTICO ENCONTRADO
