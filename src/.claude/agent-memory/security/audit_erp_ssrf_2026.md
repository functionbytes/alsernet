---
name: audit-erp-ssrf-2026
description: SSRF confirmado en modules/Erp (ERP Endpoints feature) - localizado tras auditoría previa que lo dejó sin ubicar
metadata:
  type: project
---

Segunda pasada (jul-2026) localizó el SSRF que el roadmap de Helpdesk (jun-2026, ver [[project_helpdesk_modules_roadmap]]) había dejado "sin ubicar". Está en **modules/Erp**, no en HelpdeskErp.

**Confirmado, con código en mano:**
- `ErpEndpoint::url` (modules/Erp/app/Models/ErpEndpoint.php:28) es `$fillable`, validado solo con la regla sintáctica `'url'` de Laravel (no resuelve IP, no bloquea rangos privados/metadata) en `StoreErpEndpointRequest`/`UpdateErpEndpointRequest`.
- Se pasa directo a `Http::` en 3 sitios, devolviendo la **respuesta completa** al llamante (no ciego): `ErpEndpointsApiController::test()` (líneas 117-121, body_preview en JSON), `ErpEndpointsController::test()` Settings (líneas 239-246), `PublicEndpointController::call()` (líneas 47-86, cualquier método HTTP, status+headers+body íntegros).
- `OutboundUrlGuard` (modules/Helpdesk/app/Support/OutboundUrlGuard.php) existe y protege Campaign/HelpdeskTranslate/WorkflowEngine/SendWebhookAction, pero **nunca se usa en modules/Erp** — gap de aplicación, no ausencia de la herramienta.
- Barrera de explotación muy baja: ambos Form Requests tienen `authorize() { return true; }` (viola [[feedback_...]] convención del proyecto), cero permisos `erp.*` sembrados en DB, rutas web solo `['web','auth','verified']` (sin gate), rutas API solo `auth:sanctum` sin permiso — cualquier usuario logueado o token Sanctum cualquiera puede crear un endpoint apuntando a 169.254.169.254 / localhost / red Docker interna y leer la respuesta.

**Descartado en esta pasada:**
- HelpdeskErp (ErpContextService/ErpCustomerLinkerService) — host sale de `config('helpdeskErp.manager_url')`, no de input de usuario. Sin SSRF.
- SQLi en `PrestashopProductQueryService.php` (la query que la auditoría previa no encontró) — Query Builder + `DB::selectOne` con bind params correctos, tabla/prefijo desde config no input. Sin SQLi.

**Warning adicional descubierto:** `/api/erp/customer/*` (datos GDPR/LOPD: personal, lopd, cards, accounts, debts, payments, invoices) abierto sin auth por defecto vía `ApiAuth` middleware no-op cuando `erp_api_auth_enabled` setting no existe (confirmado vacío en DB) — es "by design" documentado en comentarios ("se asume firewall externo"), no bug oculto, pero vale la pena confirmar que el firewall Docker realmente aísla esa ruta.

**Why**: el objetivo explícito de esta pasada era confirmar o descartar el SSRF pendiente; quedó confirmado y localizado con file:line exacto, no arreglado (auditoría read-only por instrucción).
**How to apply**: si se pide arreglar, usar el patrón ya existente `OutboundUrlGuard::isSafe()` en las Form Requests + antes de cada `Http::` saliente, y añadir `$this->authorize()` + seeder de permisos `erp.*` a los controllers de Erp (hoy no existen).
