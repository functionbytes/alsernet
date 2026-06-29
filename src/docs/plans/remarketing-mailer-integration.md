# Plan de Integración: Remarketing ↔ Mailer (Modo SaaS Multi-Tenant)

> **Fecha:** 2026-05-03  
> **Estado:** Fase 1 completa – bridge funcional end-to-end  
> **Contexto:** Sistema SaaS donde cada usuario puede agregar múltiples tiendas/páginas para remarketing.  

---

## 1. Resumen Ejecutivo

El módulo **Remarketing** posee su propio subsistema de plantillas (`remarketing_templates`) que duplica parcialmente las capacidades del módulo **Mailer** (`mailer_templates`, `mailer_template_langs`, `mailer_template_versions`, `mailer_layouts`).

**Contexto crítico:** Remarketing ya funciona como **SaaS multi-tenant**:
- Cada `User` puede tener múltiples `Store` (tiendas/páginas).
- Los datos se aislan por `store_id` / `user_id`.
- Los templates tienen visibilidad controlada (`is_global` vs. por tienda).

**Problema:** Mailer **NO** es multi-tenant. Sus templates solo tienen `module` (string), sin `store_id`, `user_id` ni concepto de aislamiento por cliente.

**Objetivo:** definir un camino técnico para que Remarketing consuma el motor de Mailer **sin perder el aislamiento SaaS**.

---

## 2. Arquitectura SaaS Actual (Verificada)

### 2.1 Multi-tenancy por Usuario + Tienda

```
Usuario (User)
 └── Tienda 1 (Store) → user_id = 1
 │     ├── Campaigns, Segments, Automations, Templates
 │     └── Customers, Products, Orders (sincronizados vía API)
 └── Tienda 2 (Store) → user_id = 1
 └── Tienda 3 (Store) → user_id = 1
```

**Aislamiento implementado en controllers:**
```php
$storeIds = Store::query()
    ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
    ->pluck('id');

$campaigns = Campaign::query()
    ->whereIn('store_id', $storeIds)  // ← aislamiento estricto
    ->latest()
    ->paginate(20);
```

### 2.2 Visibilidad de Templates (3 niveles)

| Nivel | Descripción | Implementación actual |
|---|---|---|
| **Privada por tienda** | Solo visible dentro de una tienda específica. | `store_id = X`, `is_global = false` |
| **Compartida por usuario** | Visible en todas las tiendas del mismo usuario. | **NO IMPLEMENTADO** actualmente |
| **Global del sistema** | Visible para todos los usuarios (plantillas prediseñadas). | `is_global = true` |

> **Gap identificado:** No existe el nivel intermedio. Un usuario con 5 tiendas debe duplicar plantillas o marcarlas como `is_global` (lo que las expone a otros usuarios).

### 2.3 CRUDs funcionales (13 tests ✅)

| Entidad | Crear | Editar | Eliminar | Notas |
|---|---|---|---|---|
| `Store` | ✅ | ✅ | ✅ | Fix aplicado: `domain` es `nullable` en `UpdateStoreRequest`. |
| `Campaign` | ✅ | ✅ | ✅ | Propaga `mailer_template_id` desde Template seleccionado. |
| `Segment` | ✅ | ✅ | ✅ | `conditions` decodificado desde JSON. |
| `Automation` | ✅ | ✅ | ✅ | Propaga `mailer_template_id` en steps de tipo `send_email`. |
| `Template` | ✅ | ✅ | ✅ | Sincroniza con Mailer vía `syncToMailer()`. Visibilidad `store`/`user`/`global`. |

**Tests:**
- `modules/Remarketing/tests/Feature/RemarketingCrudTest.php` – 16 passed, 67 assertions.
- `modules/Remarketing/tests/Feature/Jobs/SendEmailJobTest.php` – 6 passed, 20 assertions (incluye path Mailer).

---

## 3. Arquitectura de Mailer (No Multi-Tenant)

```
mailer_templates
├── uid, key, name
├── module            → 'attention' | 'core' | 'newsletter'  ← string libre
├── layout_id         → FK a mailer_layouts (global)
├── variables         → JSON array
├── is_enabled
└── is_protected

mailer_template_langs
├── mailer_template_id
├── lang_id           → multi-idioma
├── subject, preheader, content

mailer_template_versions  ← historial de cambios
mailer_layouts            ← headers/footers reutilizables
```

**Servicios clave:**
- `MailerTemplateRendererService::renderEmailTemplate()` – renderiza con variables, layouts, Twig sandboxed.
- `MailerTemplateRendererService::replaceVariables()` – reemplazo `{TAG}` o `{{ twig }}`.

**Problema para SaaS:** Mailer no tiene `store_id` ni `user_id`. Si metemos todas las plantillas de Remarketing en Mailer como `module = 'remarketing'`, cualquier usuario podría ver/editar las plantillas de otros usuarios.

---

## 4. Opciones de Integración (Respetando SaaS)

### Opción A – Motor Híbrido: `remarketing_templates` como wrapper SaaS + Mailer como renderer (Recomendada)

**Idea:**
1. `remarketing_templates` **se mantiene** como la capa SaaS (tiene `store_id`, `user_id`, `is_global`).
2. Añadir `mailer_template_id` nullable a `remarketing_templates`.
3. Cuando un usuario guarda un template en Remarketing, se crea/actualiza un `MailerTemplate` **sincronizado**.
4. El `module` de Mailer se usa como namespace: `module = 'remarketing'`.
5. El **aislamiento** se mantiene en la capa Remarketing: solo se listan templates cuyo `remarketing_template` pertenezca al usuario.
6. `SendEmailJob` renderiza vía `MailerTemplateRendererService` usando el `mailer_template_id`, luego aplica tracking pixel y rewrite de links.

**Flujo de guardado:**
```php
// TemplateController::store()
$remarketingTemplate = Template::create($data);

// Sincronizar con Mailer
$mailerTemplate = MailerTemplate::updateOrCreate(
    ['id' => $remarketingTemplate->mailer_template_id],
    [
        'key' => 'remarketing.'.$remarketingTemplate->id,
        'name' => $remarketingTemplate->name,
        'module' => 'remarketing',
        'is_enabled' => true,
    ]
);

MailerTemplateLang::updateOrCreate(
    ['mailer_template_id' => $mailerTemplate->id, 'lang_id' => $defaultLangId],
    [
        'subject' => $remarketingTemplate->subject,
        'preheader' => $remarketingTemplate->preheader,
        'content' => $remarketingTemplate->html_content,
    ]
);

$remarketingTemplate->update(['mailer_template_id' => $mailerTemplate->id]);
```

**Flujo de envío:**
```php
// SendEmailJob::getTemplateHtml()
if ($campaign?->template?->mailer_template_id) {
    $html = MailerTemplateRendererService::renderEmailTemplate(
        $campaign->template->mailerTemplate,
        $this->prepareVariables(),
        $this->resolveLangId()
    );
} else {
    $html = $campaign->template->html_content ?? '';  // fallback legacy
}

$html = $this->injectTrackingPixel($html);
$html = $this->rewriteLinks($html);
```

**Pros:**
- ✅ Aislamiento SaaS intacto (Mailer nunca se consulta directamente para listar).
- ✅ Se gana multi-idioma, versionado y layouts de Mailer inmediatamente.
- ✅ Fallback legacy: si falla la sincronización, todo sigue funcionando.
- ✅ Bajo riesgo de regresión.

**Contras:**
- ⚠️ Doble escritura (Remarketing + Mailer) en cada save.
- ⚠️ No se elimina la duplicación de tablas a corto plazo.

---

### Opción B – Extender Mailer con `user_id` / `store_id`

**Idea:** Modificar el módulo Mailer para que soporte multi-tenancy nativo.

**Cambios en Mailer:**
1. Añadir `user_id` y `store_id` nullable a `mailer_templates`.
2. Añadir scopes: `scopeForUser($query, $userId)`, `scopeForStore($query, $storeId)`.
3. Modificar todos los controllers del Mailer para aplicar aislamiento.
4. Remarketing consume `MailerTemplate` directamente, sin tabla intermedia.

**Pros:**
- ✅ Arquitectura limpia a largo plazo.
- ✅ Todos los módulos pueden beneficiarse del multi-tenancy.

**Contras:**
- ❌ **Alto riesgo:** Mailer es usado por Attention, Newsletter, Helpdesk, etc. Cambiar su schema afecta a todos.
- ❌ Requiere modificar permisos Spatie en múltiples módulos.
- ❌ Mayor esfuerzo (~4–6 semanas).

---

### Opción C – Namespace por Tenant (`module` como identificador)

**Idea:** Usar el campo `module` de Mailer como tenant: `module = 'remarketing.user_123'`.

**Problemas:**
- ❌ No hay índice eficiente para buscar "todos los templates del usuario 123".
- ❌ Los layouts (`mailer_layouts`) seguirían siendo globales.
- ❌ El versionado no está ligado al tenant.
- ❌ Hack arquitectónico; difícil de mantener.

**Veredicto:** Descartada.

---

## 5. Recomendación

**Adoptar Opción A (Motor Híbrido / Wrapper SaaS)** por:

1. **Seguridad SaaS:** el aislamiento nunca se rompe porque la capa de autorización sigue en `remarketing_templates`.
2. **Incremental:** cada fase se puede desplegar sin afectar otros módulos.
3. **Aprendizaje:** permite validar que el renderizado de Mailer cubre las necesidades de Remarketing (tracking, unsubscribe, variables) antes de comprometerse a un refactor masivo.
4. **Escalable:** si en el futuro se decide extender Mailer (Opción B), la migración de datos es trivial porque ya existen los `mailer_template_id`.

---

## 6. Mejora Inmediata: Nivel de Visibilidad "Compartida por Usuario"

Antes o durante la integración con Mailer, se recomienda añadir el nivel de visibilidad faltante:

**Migración:**
```php
Schema::table('remarketing_templates', function (Blueprint $table) {
    $table->enum('visibility', ['store', 'user', 'global'])
          ->default('store')
          ->after('store_id');
});
```

**Lógica de listado:**
```php
$templates = Template::query()
    ->where(function ($q) use ($storeIds, $userId) {
        $q->whereIn('store_id', $storeIds)           // mis tiendas
          ->orWhere(function ($q2) use ($userId) {
              $q2->where('visibility', 'user')
                 ->whereHas('store', fn ($s) => $s->where('user_id', $userId));
          })
          ->orWhere('visibility', 'global');          // del sistema
    })
    ->latest()
    ->paginate(20);
```

Esto permite que un usuario con 5 tiendas cree una plantilla una sola vez y la reutilice en todas sus tiendas, sin exponerla a otros usuarios.

---

## 7. Plan de Implementación (Opción A)

### Fase 1 – Bridge + Visibilidad ✅ COMPLETA

| # | Tarea | Archivos | Estado |
|---|---|---|---|
| 1.1 | Añadir `visibility` (`store`\|`user`\|`global`) a `remarketing_templates` | migración + modelo | ✅ |
| 1.2 | Actualizar `TemplateController::index()` para filtrar por visibilidad | controller | ✅ |
| 1.3 | Actualizar vistas de template para selector de visibilidad | `templates/form.blade.php` | ✅ |
| 1.4 | Añadir `mailer_template_id` a `remarketing_templates` | migración + modelo | ✅ |
| 1.5 | Sincronizar create/update con Mailer | `TemplateController::store()`, `update()` | ✅ |
| 1.6 | Renderizado condicional en `SendEmailJob` | `SendEmailJob::getTemplateHtml()` | ✅ |
| 1.7 | Propagar `mailer_template_id` en Campaign y Automation | `CampaignController`, `AutomationController` | ✅ |
| 1.8 | Tests de regresión + Pint | `RemarketingCrudTest` + `SendEmailJobTest` | ✅ |

### Fase 2 – Multi-idioma + Versionado (semanas 3–4)

| # | Tarea | Archivos | Esfuerzo |
|---|---|---|---|
| 2.1 | UI para gestionar traducciones de un template | nueva vista o modal | 4 h |
| 2.2 | Sincronizar traducciones a `mailer_template_langs` | controller / service | 2 h |
| 2.3 | Selector de idioma al crear campaña | `campaigns/form.blade.php` | 2 h |
| 2.4 | Mostrar historial de versiones | vista + API | 3 h |

### Fase 3 – Layouts + Variables Tipadas (semanas 5–6)

| # | Tarea | Archivos | Esfuerzo |
|---|---|---|---|
| 3.1 | Permitir asignar `layout_id` de Mailer a un template | selector en form | 2 h |
| 3.2 | Definir variables estándar de Remarketing en `mailer_variables` | seeder | 2 h |
| 3.3 | Validar variables requeridas antes de enviar campaña | `CampaignService::send()` | 2 h |
| 3.4 | Preview con variables de ejemplo | endpoint AJAX | 2 h |

**Esforzo total estimado:** ~30–35 horas de desarrollo + QA.

---

## 8. Consideraciones Técnicas SaaS

### 8.1 Tracking pixel y rewrite de links

`MailerTemplateRendererService::renderEmailTemplate()` devuelve HTML final. Las transformaciones de Remarketing deben ejecutarse **después**:

```php
$html = MailerTemplateRendererService::renderEmailTemplate($mailerTemplate, $variables, $langId);
$html = $this->injectTrackingPixel($html);   // pixel de apertura
$html = $this->rewriteLinks($html);            // tracking de clicks
```

### 8.2 Variables por tienda

Remarketing usa variables simples (`{{firstName}}`, `{{unsubscribeUrl}}`). Mailer usa `{CUSTOMER_NAME}` o Twig.

**Estrategia de compatibilidad:**
```php
protected function prepareVariables(): array
{
    $customer = $this->message->customer;
    $unsubUrl = $this->buildUnsubscribeUrl();

    return [
        // Formato Mailer (nuevo)
        'CUSTOMER_NAME' => $customer?->first_name ?? '',
        'CUSTOMER_LASTNAME' => $customer?->last_name ?? '',
        'CUSTOMER_EMAIL' => $this->message->email,
        'UNSUBSCRIBE_URL' => $unsubUrl,
        'STORE_NAME' => $this->message->store->name,
        'STORE_DOMAIN' => $this->message->store->domain,

        // Legacy compat (templates antiguos)
        '{{firstName}}' => $customer?->first_name ?? '',
        '{{lastName}}' => $customer?->last_name ?? '',
        '{{email}}' => $this->message->email,
        '{{unsubscribeUrl}}' => $unsubUrl,
        '{{UNSUBSCRIBE_URL}}' => $unsubUrl,
    ];
}
```

### 8.3 Aislamiento de Layouts

Los `mailer_layouts` actuales son globales. En SaaS, los usuarios eventualmente querrán layouts personalizados.

**Solución a corto plazo:** usar layouts globales del sistema (branding de la plataforma).  
**Solución a largo plazo:** extender `mailer_layouts` con `user_id` (Opción B).

### 8.4 Límites por plan (futuro)

Si se implementa un módulo de Billing/Planes:

```php
// Ejemplo de gate por plan
$maxTemplates = $user->plan->limit('remarketing.templates');
$currentCount = Template::whereHas('store', fn ($q) => $q->where('user_id', $user->id))->count();

if ($currentCount >= $maxTemplates) {
    throw new PlanLimitExceededException('Límite de plantillas alcanzado.');
}
```

La capa `remarketing_templates` es el lugar correcto para aplicar estos límitos porque conoce el `user_id`.

---

## 9. Métricas de Éxito

- [x] Usuario puede crear template y usarlo en cualquiera de sus tiendas (visibilidad `user`).
- [x] Templates globales del sistema (`global`) visibles para todos.
- [x] `SendEmailJob` renderiza vía Mailer cuando `mailer_template_id` existe.
- [x] Fallback legacy funciona si Mailer no está disponible.
- [ ] Preview de campaña muestra HTML con layout + variables reemplazadas.
- [x] Todos los tests CRUD existentes siguen pasando.
- [x] Nuevos tests de regresión para envío con Mailer pasan.

---

## 10. Adjuntos

- **Tests CRUD:** `modules/Remarketing/tests/Feature/RemarketingCrudTest.php` (16 passed, 67 assertions).
- **Tests Jobs:** `modules/Remarketing/tests/Feature/Jobs/SendEmailJobTest.php` (6 passed, 20 assertions).
- **Fix aplicado:** `UpdateStoreRequest` – `domain` cambiado a `nullable`.
- **Patrón de referencia:** `modules/Attention/app/Services/AttentionEmailTemplateService.php`
