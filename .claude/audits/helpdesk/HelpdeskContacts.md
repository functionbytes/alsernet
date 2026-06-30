# Auditoría — HelpdeskContacts

> Fecha: 2026-06-29 · Health score: 66/100 · Estado: solid-minor-issues

**Resumen:** Módulo de contactos CRM 360 bien arquitecturado, con guards defensivos por módulo y gating de permisos consistente, pero con un fallo crítico de route-model-binding en el controlador de fusión que puede corromper datos, más una brecha de XSS en la UI de merge. El diseño es sólido (degradación elegante de pestañas, permisos Spatie homogéneos, escapado HTML en JS), pero la ruta de fusión está completamente rota a nivel HTTP y queda sin tests, y persisten desviaciones de convención (validación inline, ausencia de Policy, permisos sembrados sin cablear).

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HC-001 | critical | wiring | ContactsMergeController.php:54,80 | [CONFIRMADO] | S | Mismatch route-model binding ($winner vs {customer}) inyecta un Customer vacío |
| HC-002 | high | security | contacts-360.js:1532-1555 | [CONFIRMADO] | S | XSS almacenado en búsqueda/preview de merge (name/email sin escapar) |
| HC-003 | medium | quality | contacts/show.blade.php:339 | [CONFIRMADO] | S | filemtime(public_path(...)) sobre asset no publicado sin fallback |
| HC-004 | medium | conventions | ContactsController.php:206 | [CONFIRMADO] | M | Validación inline en lugar de Form Requests (bulkAction y carrito) |
| HC-005 | medium | conventions | HelpdeskContactsServiceProvider.php:24-46 | [CONFIRMADO] | M | Sin Policy; autorización solo vía middleware can: de ruta |
| HC-006 | medium | security | ContactsController.php:204-231 | [CONFIRMADO] | S | bulkAction delete/ban masivo sin token de confirmación ni límite |
| HC-007 | low | ux | contacts/index.blade.php:229 | [CONFIRMADO] | S | Atributos style="" inline en blades |
| HC-008 | low | wiring | HelpdeskContactsPermissionsSeeder.php:19-20 | [CONFIRMADO] | S | Permisos contacts.commerce y contacts.insights nunca usados |
| HC-009 | low | quality | ContactsController.php:43 | [CONFIRMADO] | S | index() pasa filtro 'health' que applyFilters nunca maneja |
| HC-010 | low | security | ContactsMergeController.php:102-107 | [CONFIRMADO] | S | Merge execute filtra el mensaje de excepción al cliente |
| HC-011 | low | quality | ContactsController.php:184-199 | [CONFIRMADO] | S | ban/unban evitan los helpers del modelo ban()/unban() |
| HC-012 | low | tests | tests/Feature | [CONFIRMADO] | M | Gaps de cobertura: import/export/bulkAction/ban/cart/merge-controller |

## Hallazgos detallados

### HC-001 · [CONFIRMADO] · critical · wiring
**Mismatch route-model binding ($winner vs {customer}) inyecta un Customer vacío**
`modules/HelpdeskContacts/app/Http/Controllers/Managers/ContactsMergeController.php:54,80`

**Evidencia:** Las rutas definen el prefijo `/{customer}/merge` (`routes/web.php:51`) pero `preview(Customer $winner, ...)` y `execute(Customer $winner, ExecuteMergeRequest $request)` tipan el parámetro como `$winner`. El binding implícito de Laravel resuelve por NOMBRE de parámetro, así que el valor de `{customer}` nunca se vincula a `$winner`; el contenedor inyecta un `Customer` vacío (id=null). En `execute()` ese winner vacío se pasa a `mergeService->merge($winner, $loser)`, que ejecuta UPDATEs poniendo `customer_id => $winner->id` (null) en conversations/tickets/widget_sessions y copia IDs de integración a un modelo sin persistir.

Verificación: confirmado por completo. No existen `Route::bind`/`Route::model` en el codebase, y `winner` no aparece en ningún segmento de ruta — `SubstituteBindings` resuelve por coincidencia de nombre. El contenedor resuelve `$winner` vía reflexión como `new Customer()` (id=null, exists=false). En `execute()`: (1) el guard de auto-merge (`$loserId === $winner->id`) compara entero vs null — siempre false; (2) `mergeService->merge()` ejecuta `UPDATE helpdesk_conversations SET customer_id = NULL WHERE customer_id = {loser_id}`: si `customer_id` tiene FK/NOT NULL, MariaDB rechaza el UPDATE y revierte la transacción (respuesta 500); si es nullable, las conversaciones quedan huérfanas. En cualquier caso la fusión es completamente no funcional. `ContactMergeServiceTest` evita el controlador y prueba el servicio aislado, así que esto no tiene cobertura a nivel HTTP.

**Impacto:** Fusionar duplicados corrompe datos silenciosamente: los registros relacionados se repuntan a un `customer_id` null/0 y el winner previsto nunca se actualiza. Funcionalidad rota.

**Recomendación:** Renombrar los parámetros a `$customer` (coincidiendo con el segmento de ruta) en `preview()` y `execute()`, ej. `preview(Customer $customer, Request $request)`. Añadir un feature test HTTP a `merge.execute` que afirme que el winner se actualiza realmente, para fijar el binding.

---

### HC-002 · [CONFIRMADO] · high · security
**XSS almacenado en búsqueda/preview de merge (name/email sin escapar)**
`modules/HelpdeskContacts/resources/assets/js/contacts-360.js:1532-1555`

**Evidencia:** Los resultados de búsqueda y el preview de merge construyen HTML por concatenación e inyectan con `.html()`: `'<div class="fw-semibold">' + (c.name || '—') + '</div>' ... (c.email || '—')` y el bloque de preview usa `(w.name)`, `(w.email)`, `(l.name)`, `(l.email)` — ninguno envuelto en `esc()`, a diferencia del resto de renderers del archivo.

Verificación: confirmado. El helper `esc()` (línea 54) se invoca en 40+ puntos para toda salida controlada por el usuario. El renderer de búsqueda (líneas 1532–1533) concatena `(c.name || '—')` y `(c.email || '—')` directamente en el string pasado a `.html()`. El renderer de preview (líneas 1550–1555) hace lo mismo con `w.name`, `w.email`, `l.name`, `l.email`. Un grep confirma que estas seis líneas son las únicas del archivo donde name/email llegan al DOM sin escapar. name/email es seteable vía import CSV o chat/email entrante sin permiso `contacts.merge`, así que un payload puede plantarlo cualquiera que envíe un mensaje o aparezca en un import; se ejecuta en el navegador del agente al abrir el modal de merge.

**Impacto:** XSS almacenado: un name/email con markup ejecuta en el navegador del agente al abrir el modal de fusión. Limitado a holders de `contacts.merge` pero sigue siendo ruta de stored-XSS.

**Recomendación:** Envolver `c.name`/`c.email` y `w/l.name`/`w/l.email` con el helper `esc()` existente, consistente con el resto de renderers.

---

### HC-003 · [CONFIRMADO] · medium · quality
**filemtime(public_path(...)) sobre asset no publicado sin fallback**
`modules/HelpdeskContacts/resources/views/contacts/show.blade.php:339`

**Evidencia:** `<script src="{{ asset('modules/contacts/js/contacts-360.js') }}?v={{ filemtime(public_path('modules/contacts/js/contacts-360.js')) }}">`. El asset fuente vive en `resources/assets/js/contacts-360.js`; `public/modules/contacts` no existe en este checkout. `filemtime()` sobre un path inexistente devuelve `false` y emite un E_WARNING, y el `src` del script da 404, dejando todo el shell de pestañas 360 no funcional hasta copiar el asset manualmente (gotcha conocido del proyecto "module CSS/JS requiere cp manual").

**Impacto:** Si se omite el paso de publish/copy en deploy, la página 360 no renderiza datos y lanza un warning PHP; el cache-busting degrada silenciosamente.

**Recomendación:** Proteger con `file_exists()`/`@filemtime` o asset versionado vía Vite/manifest, y asegurar un paso de publish que copie `resources/assets` a `public/modules/contacts`.

---

### HC-004 · [CONFIRMADO] · medium · conventions
**Validación inline en lugar de Form Requests (bulkAction y proxy de carrito)**
`modules/HelpdeskContacts/app/Http/Controllers/Managers/ContactsController.php:206`

**Evidencia:** `ContactsController::bulkAction` usa `$request->validate([...])` inline (líneas 206-216). `ContactCartController` también valida inline: `addItem` (línea 50), `applyDiscount` (línea 97), `validateCustomerData` (línea 169). Regla del proyecto: "Usar Form Request para TODA validación (nunca inline `$request->validate()`)".

**Impacto:** Viola la convención documentada controller/form-request; validación/mensajes no reutilizables ni en español.

**Recomendación:** Extraer `BulkActionContactsRequest` y Form Requests de carrito (`AddCartItemRequest`, `ApplyDiscountRequest`, `GenerateOrderRequest`) con `messages()`/`attributes()` en español y `authorize()` verificando `contacts.update`.

---

### HC-005 · [CONFIRMADO] · medium · conventions
**Sin clase Policy; autorización solo vía middleware can: de ruta**
`modules/HelpdeskContacts/app/Providers/HelpdeskContactsServiceProvider.php:24-46`

**Evidencia:** El ServiceProvider registra rutas/nav pero no `Gate::policy()` / `registerPolicies()`. Toda la autz es middleware `can:contacts.*` a nivel de ruta; no hay chequeo de ownership por registro en update/ban/delete/merge. `policies.md` exige un `{Entity}Policy` registrado vía `Gate::policy`.

**Impacto:** Cualquier holder de `contacts.update` puede editar/banear/borrar-en-masa/fusionar CUALQUIER contacto sin scoping de propiedad. Aceptable para un CRM admin pero diverge de la convención de policies y no ofrece defensa en profundidad más allá del permiso plano.

**Recomendación:** Si se desea scoping por ownership, añadir un `ContactPolicy` y llamadas `authorize()`; en caso contrario documentar el modelo de permiso plano intencional en CONTEXT.md.

---

### HC-006 · [CONFIRMADO] · medium · security
**bulkAction delete/ban realiza updates masivos destructivos sin token de confirmación ni rate limit**
`modules/HelpdeskContacts/app/Http/Controllers/Managers/ContactsController.php:204-231`

**Evidencia:** `bulkAction` acepta un array `ids[]` arbitrario y ejecuta `Customer::whereIn('id',$ids)->delete()/update()` de una vez; la ruta solo tiene throttling web por defecto. `delete()` es soft-delete (Customer usa SoftDeletes) lo que mitiga la permanencia, pero ban/delete sobre un id-set grande es ilimitado.

**Impacto:** Una sola petición de cualquier usuario `contacts.update` puede banear o soft-eliminar todos los contactos cuyos ids se suministren; sin límite superior al tamaño del array.

**Recomendación:** Limitar el tamaño del array (ej. `max:500`) y envolver en `DB::transaction()`; considerar un flag de confirmación explícito para delete.

---

### HC-007 · [CONFIRMADO] · low · ux
**Atributos style="" inline en blades**
`modules/HelpdeskContacts/resources/views/contacts/index.blade.php:229`

**Evidencia:** `index.blade.php:229` `<span style="color:#c13584" ...>` (color Instagram) y `reports.blade.php:98` `style="width:32px;height:32px"`. Regla del proyecto: nunca usar style="" inline.

**Impacto:** Violación menor de convención; mantenimiento de theming más difícil.

**Recomendación:** Mover a clases CSS utilitarias (show.blade ya usa un bloque `@push('css')` para dimensionado similar de avatares).

---

### HC-008 · [CONFIRMADO] · low · wiring
**Permisos sembrados contacts.commerce y contacts.insights nunca usados**
`modules/HelpdeskContacts/database/seeders/HelpdeskContactsPermissionsSeeder.php:19-20`

**Evidencia:** Un grep en el módulo muestra que `contacts.commerce` / `contacts.insights` solo aparecen en el seeder y CONTEXT.md — sin referencias en middleware de ruta, controlador ni Form Request. Las rutas de carrito/commerce están gateadas en `contacts.update`, no en `contacts.commerce`.

**Impacto:** Permisos muertos generan confusión sobre el modelo de acceso; las funciones de carrito/commerce no están realmente gateadas por el permiso commerce que implican.

**Recomendación:** Cablear `contacts.commerce` en las rutas de carrito y `contacts.insights` en reports/tabs, o eliminarlos del seeder.

---

### HC-009 · [CONFIRMADO] · low · quality
**index() pasa un filtro 'health' que applyFilters nunca maneja**
`modules/HelpdeskContacts/app/Http/Controllers/Managers/ContactsController.php:43`

**Evidencia:** La vista recibe `'filters' => $request->only(['q','channel','health','last_seen','verified','banned'])` pero `applyFilters()` (líneas 271-311) no tiene rama para `'health'`.

**Impacto:** Filtro muerto/a medio cablear — un filtro health en la UI haría no-op silencioso.

**Recomendación:** Implementar el filtro health en applyFilters o eliminar la clave.

---

### HC-010 · [CONFIRMADO] · low · security
**Merge execute filtra el mensaje de excepción crudo al cliente**
`modules/HelpdeskContacts/app/Http/Controllers/Managers/ContactsMergeController.php:102-107`

**Evidencia:** `catch (Throwable $e) { return ...['message' => 'Error al fusionar los contactos: '.$e->getMessage()], 500); }`.

**Impacto:** Detalles internos de DB/excepción expuestos al navegador (divulgación de información).

**Recomendación:** Devolver un mensaje genérico en español y `Log::error` la excepción con contexto, reflejando `ContactTabsController::createTicket`.

---

### HC-011 · [CONFIRMADO] · low · quality
**ban/unban evitan los helpers ban()/unban() del modelo**
`modules/HelpdeskContacts/app/Http/Controllers/Managers/ContactsController.php:184-199`

**Evidencia:** `ban()` hace `$customer->update(['banned_at'=>now()])` y `bulkAction` hace update masivo `whereIn`, en lugar de los helpers `Customer::ban($reason)`/`unban()` (`Customer.php:230-250`) que también limpian/setean `ban_reason`. `ban_reason` nunca se setea/limpia aquí; el activity-log `logOnly(['banned_at'])` sigue disparando pero `ban_reason` queda desincronizado.

**Impacto:** `ban_reason` queda obsoleto en unban y nunca se captura en ban; lógica duplicada entre modelo y controlador.

**Recomendación:** Llamar `$customer->ban()`/`$customer->unban()` para mantener `ban_reason` consistente.

---

### HC-012 · [CONFIRMADO] · low · tests
**Gaps de cobertura: import/export/bulkAction/ban/cart/merge-controller sin tests**
`modules/HelpdeskContacts/tests/Feature`

**Evidencia:** Tres Feature tests cubren index/show authz+search, los 8 endpoints de pestaña, sync authz, y `ContactMergeService` directamente. No existen tests para `importProcess`, export CSV, `bulkAction`, ban/unban, las rutas HTTP de `ContactsMergeController` (search/preview/execute), ni `ContactCartController`. El bug de binding (HC-001) lo habría detectado un test HTTP de `merge.execute`.

**Impacto:** La ruta crítica de fusión y el import/export CSV corren sin protección de regresión.

**Recomendación:** Añadir Feature tests para `merge.execute` (afirmando que el winner se actualiza), `bulkAction`, ban/unban e import CSV upsert.

## Plan de ataque priorizado

1. **HC-001 (critical):** Renombrar parámetros `$winner` → `$customer` en `preview()`/`execute()`. Sin esto la fusión está rota o corrompe datos. Acompañar con feature test HTTP de `merge.execute`.
2. **HC-002 (high):** Envolver name/email en `esc()` en líneas 1532-1555 del JS. Cierra la ruta de XSS almacenado.
3. **HC-003 (medium):** Proteger `filemtime` con fallback y garantizar paso de publish del asset.
4. **HC-006 (medium):** Limitar tamaño de `ids[]` y transaccionar bulkAction.
5. **HC-004 / HC-005 (medium):** Migrar validación inline a Form Requests; decidir Policy vs documentar permiso plano.
6. **HC-012 (low/tests):** Cerrar gaps de cobertura (merge.execute, bulkAction, ban, CSV).
7. Resto de low (HC-007 a HC-011) como limpieza.

## Quick wins

- Eliminar o cablear los permisos sembrados sin uso `contacts.commerce` y `contacts.insights` (HC-008).
- Quitar la clave muerta `health` del array de filtros de `index()` (HC-009).
- Dejar de filtrar `$e->getMessage()` al cliente en `ContactsMergeController::execute` (HC-010).
- Convertir los dos `style=""` inline (index.blade:229, reports.blade:98) a clases CSS (HC-007).
- Usar los helpers `ban()`/`unban()` del modelo en vez de update directo (HC-011).

## Fortalezas

- **Diseño defensivo excelente:** cada dependencia cross-módulo opcional (ERP, PrestaShop, Remarketing, Tickets, EmailLog, Livechat, Social, Ecommerce) se resuelve tras guards `Module::find()`+`class_exists()` con FQCN literales, así el panel nunca da 500 cuando un módulo está deshabilitado.
- Los endpoints de pestaña degradan elegantemente a `{available:false}` ante cualquier `Throwable` en lugar de filtrar 500s (`ContactTabsController::tab`).
- Gating de permisos Spatie consistente a nivel de ruta (`can:contacts.view/update/merge`) más `authorize()` correspondiente en Form Requests; los permisos siguen la convención lowercase `{alias}.{action}`.
- El JS usa un escapador HTML real `esc()` aplicado consistentemente en los renderers principales (resumen, conversaciones, erp, etc.).
- `scopeSearch` usa parámetros LIKE bindeados (sin inyección SQL); el export CSV es chunked y limitado a 5000 filas.
- Los Form Requests tienen `messages()` y `attributes()` en español; las acciones de tabla usan el dropdown `fa-ellipsis-vertical` requerido; Font Awesome 6 únicamente (sin Tabler).

## Cobertura de la auditoría

Revisados los 4 controladores, 3 servicios, 4 form requests, ServiceProvider, rutas, seeder, el asset `contacts-360.js` (1618 líneas) y los 5 blades. Cross-check de `Modules\Helpdesk\Models\Customer` (fillable, scopeSearch, helpers ban). Tests DB-backed no ejecutados por instrucciones (DB de test bloqueada) — solo análisis estático. No se auditaron en profundidad el bridge/servicios externos de HelpdeskErp/HelpdeskPrestashop/Remarketing/HelpdeskTickets a los que este módulo hace proxy (fuera del alcance del módulo); todos están tras guards `Module::find()`+`class_exists()`.

## Descartados en verificación

Ninguno. Los 12 hallazgos fueron confirmados en verificación; no hubo refutaciones.
