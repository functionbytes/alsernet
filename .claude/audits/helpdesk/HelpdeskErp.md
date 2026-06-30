# Auditoría — HelpdeskErp

> Fecha: 2026-06-29 · Health score: 85/100 · Estado: solid-minor-issues

**Resumen:** Módulo de integración ERP (Oracle vía manager-bridge) bien construido: webhook con HMAC, caché stale-while-revalidate, PII masking, jobs con queue/unique y ~81 tests; los defectos son menores, salvo un acceso a tablas de Helpdesk por la conexión equivocada. El módulo es sólido y está prácticamente listo para producción; el único hallazgo de severidad media es un bug latente que solo se manifestaría si las BD de Helpdesk y default dejaran de compartir base de datos. El resto son inconsistencias de convención y endurecimientos defensivos.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HE-1 | medium | wiring | modules/HelpdeskErp/app/Services/CustomerTimelineService.php:234-263 | [CONFIRMADO] | S | Tablas de Helpdesk consultadas en la conexión por defecto en vez de 'helpdesk' |
| HE-2 | low | quality | modules/HelpdeskErp/app/Http/Controllers/Api/ErpContextController.php:99-119 | [CONFIRMADO] | S | refresh() valida y autoriza inline en vez de usar Form Request como el resto |
| HE-3 | low | conventions | modules/HelpdeskErp/database/seeders/HelpdeskErpPermissionsSeeder.php:17-22 | [CONFIRMADO] | M | Permisos Spatie en camelCase (helpdeskErp.*) en vez de minúsculas |
| HE-4 | low | quality | modules/HelpdeskErp/app/Services/CustomerTimelineService.php:63 | [CONFIRMADO] | S | Ordenación del timeline depende de strtotime() sobre fechas heterogéneas |
| HE-5 | low | quality | modules/HelpdeskErp/app/Services/ErpCustomerLinkerService.php:103 | [CONFIRMADO] | S | Búsqueda por teléfono devuelve results[0] sin verificación exacta |
| HE-6 | low | security | modules/HelpdeskErp/routes/api.php:37-44 | [CONFIRMADO] | S | Endpoints health y cache.warm sin auditoría audit.access |

## Hallazgos detallados

### HE-1 · [CONFIRMADO] · medium · wiring
**Tablas de Helpdesk consultadas en la conexión por defecto en vez de 'helpdesk'**
`modules/HelpdeskErp/app/Services/CustomerTimelineService.php:234-263`

- **Evidencia:** `collectHelpdeskEvents()` usa `Schema::hasTable('helpdesk_conversations')` y `DB::table('helpdesk_conversations')` sin `->connection('helpdesk')`. Las entidades Helpdesk viven en una conexión separada (`Customer.php:21` `protected $connection = helpdesk`; `config/database.php:199` con `DB_DATABASE_HELPDESK` propio). Mismo patrón en `WarmErpCacheCommand::collectEmails()` (`DB::table`/`Schema` sobre `helpdesk_customers`/`conversations`/`tickets`).
- **Impacto:** Si `DB_DATABASE_HELPDESK` apunta a una BD distinta de la default, `Schema::hasTable` devuelve false y los eventos de conversaciones nunca aparecen en el timeline (fallo silencioso, atrapado por try/catch). El warm-cache tampoco encolaría emails. Funciona hoy solo porque comparten BD.
- **Recomendación:** Usar `DB::connection('helpdesk')->table(...)` y `Schema::connection('helpdesk')->hasTable(...)/hasColumn(...)` en ambos sitios; o mejor reutilizar los modelos Eloquent de Helpdesk que ya fijan la conexión.
- **Esfuerzo:** S

### HE-2 · [CONFIRMADO] · low · quality
**refresh() valida y autoriza inline en vez de usar Form Request como el resto**
`modules/HelpdeskErp/app/Http/Controllers/Api/ErpContextController.php:99-119`

- **Evidencia:** `refresh()` hace `filter_var(FILTER_VALIDATE_EMAIL)` y comprueba `can('helpdeskErp.view')`/`can('helpdeskErp.refresh')` a mano, mientras `show()`/`timeline()` usan `CustomerContextRequest`. La misma validación de email se duplica además en `ErpContextWebController::context()` (línea 22) y `WebhookController`.
- **Impacto:** Inconsistencia con las reglas del proyecto (Form Request obligatorio) y duplicación de validación de email en 4 puntos.
- **Recomendación:** Crear un Form Request (p.ej. `RefreshCustomerContextRequest` con `authorize()` de `helpdeskErp.refresh`) y centralizar la validación de email; reutilizar entre controllers web/api.
- **Esfuerzo:** S

### HE-3 · [CONFIRMADO] · low · conventions
**Permisos Spatie en camelCase (helpdeskErp.*) en vez de minúsculas**
`modules/HelpdeskErp/database/seeders/HelpdeskErpPermissionsSeeder.php:17-22`

- **Evidencia:** Permisos `'helpdeskErp.view'`, `'helpdeskErp.refresh'`, `'helpdeskErp.health.view'`, `'helpdeskErp.orders.detail.view'` usan la 'E' mayúscula del alias. La convención del proyecto (CLAUDE.md, 2026-04-27) exige `'{alias}.{action}'` en lowercase exclusivo.
- **Impacto:** Desviación de convención; consistente internamente, sin impacto funcional, pero rompe la uniformidad de permisos del sistema.
- **Recomendación:** Decidir alias en minúsculas (`helpdesk-erp` o `helpdeskerp`) y migrar permisos+gates de forma coordinada, o documentar la excepción junto a la de naming de Helpdesk.
- **Esfuerzo:** M

### HE-4 · [CONFIRMADO] · low · quality
**Ordenación del timeline depende de strtotime() sobre fechas heterogéneas**
`modules/HelpdeskErp/app/Services/CustomerTimelineService.php:63`

- **Evidencia:** `usort($events, fn ($a,$b) => strtotime($b['date']) - strtotime($a['date']))`. Las fechas vienen sin normalizar de ERP (`fpedido`/`ffactura`), PrestaShop (`date_add`/`date_upd`) y Helpdesk (`created_at`). `strtotime()` de un formato no reconocido devuelve false (epoch 0).
- **Impacto:** Eventos con fecha no parseable se ordenan como 1970 y quedan al final; orden cronológico potencialmente incorrecto para formatos ERP atípicos.
- **Recomendación:** Normalizar cada fecha con `Carbon::parse()` (con try/catch) a timestamp antes del `usort`, descartando o registrando las no parseables.
- **Esfuerzo:** S

### HE-5 · [CONFIRMADO] · low · quality
**Búsqueda por teléfono devuelve results[0] sin verificación exacta**
`modules/HelpdeskErp/app/Services/ErpCustomerLinkerService.php:103`

- **Evidencia:** `searchByPhone()` retorna `(int) $results[0]['id']` sin confirmar coincidencia exacta, a diferencia de `searchByEmail()` y `searchCustomer()` (`ErpContextService.php:390`) que comparan el email exacto explícitamente y comentan que evitan `results[0]` para no atribuir pedidos de otro cliente. `searchCustomerByPhone` (`ErpContextService.php:407`) hace lo mismo.
- **Impacto:** Si la búsqueda fuzzy por teléfono del manager devuelve más de un candidato, se vincula/atribuye el primero, con riesgo de cruzar datos comerciales entre clientes.
- **Recomendación:** Verificar la coincidencia de dígitos del teléfono contra el resultado antes de aceptar el id, o exigir un único resultado exacto.
- **Esfuerzo:** S

### HE-6 · [CONFIRMADO] · low · security
**Endpoints health y cache.warm sin auditoría audit.access**
`modules/HelpdeskErp/routes/api.php:37-44`

- **Evidencia:** Todas las rutas de cliente llevan middleware `audit.access:erp,<accion>`, pero `/health` y `/cache/warm` solo verifican permiso en el controller, sin registro de auditoría de acceso. `cache.warm` dispara jobs con hasta 50 emails.
- **Impacto:** Acción administrativa (warm-cache, exposición de estado del bridge) no queda registrada en la pista de auditoría de acceso a datos como el resto.
- **Recomendación:** Añadir `audit.access:erp,cache_warm` y `audit.access:erp,health_view` a estas rutas para coherencia de auditoría.
- **Esfuerzo:** S

## Plan de ataque priorizado

1. **HE-1** — corregir conexión 'helpdesk' en `CustomerTimelineService` y `WarmErpCacheCommand` (bug latente multi-BD). *(medium · S)*
2. **HE-2** — mover validación/autorización de `refresh` a Form Request y eliminar duplicación de validación de email. *(low · S)*
3. **HE-3** — alinear naming de permisos con la convención lowercase o documentar la excepción. *(low · M)*

## Quick wins

- **HE-4:** normalizar fechas con Carbon antes del `usort` del timeline.
- **HE-5:** verificar coincidencia exacta de teléfono antes de aceptar `results[0]`.
- **HE-6:** añadir middleware `audit.access` a `/health` y `/cache/warm`.

## Fortalezas

- Webhook orders-ready con verificación HMAC-SHA256 + timestamp ±5min + `hash_equals` + rate limit (`WebhookController.php:62-89`); patrón seguro y completo.
- Seguridad consistente: todas las rutas API bajo `auth:sanctum`+throttle, permisos Spatie verificados en cada acción, `PiiMasker::email` en logs/Pulse, sin `DB::raw`/`whereRaw`/`{!! !!}`/`env()` fuera de config.
- Resiliencia bien pensada: caché positivo/negativo diferenciado, stale-while-revalidate con `RefreshErpContextJob`, `Http::pool()` para paralelizar las 4 llamadas al manager, fallback directo a Oracle (oci8) evitando deadlock HTTP circular.
- Jobs correctos: `ShouldQueue`+`ShouldBeUnique`, `tries`/`timeout`/`backoff`/`failed()` definidos, colas nombradas (`helpdesk-erp`, `helpdesk-erp-warming`).
- Cobertura de tests amplia: 10 ficheros Feature (~81 métodos test) cubriendo webhook, búsqueda, timeline, linking, caché, WhatsApp.
- Listener registrado como clase (no closure), compatible con `event:cache`; evento `ErpOrdersReady` con `PrivateChannel` y `broadcastWith` hasheado.

## Cobertura de la auditoría

Revisados al 100% los 31 PHP: ServiceProvider, routes (api/managers), ambos controllers API + web, WebhookController, los 3 servicios, los 3 jobs, listener, evento, 2 comandos, Form Request, Resource, seeder, config y la única Blade. No hay Models/migraciones propias (módulo de integración que reutiliza `Helpdesk\Customer` y `Erp\ErpCustomerDataService`). No se ejecutaron tests (BD de test bloqueada) — solo conteo estático (~81 métodos en 10 ficheros Feature, sin Unit). No se auditó en profundidad el JS del inbox que consume estos endpoints (vive en el módulo Helpdesk, fuera de alcance).

## Descartados en verificación

Sin hallazgos critical/high que verificar. Ningún hallazgo fue refutado durante la verificación: los 6 quedan confirmados con su severidad original.
