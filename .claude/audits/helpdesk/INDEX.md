# Auditoría Helpdesk — Índice maestro (2026-06-29)

17 módulos auditados. **2 críticos confirmados** y **10 highs confirmados** repartidos en 8 módulos. **176 hallazgos** en total.

## Ranking (peor salud / más críticos primero)

| # | Módulo | Health | Estado | #Crit | #High | Total | Reporte |
|---|--------|:------:|--------|:-----:|:-----:|:-----:|---------|
| 1 | HelpdeskSocial | 56 | needs-work | 1 | 1 | 14 | [reporte](./HelpdeskSocial.md) |
| 2 | HelpdeskContacts | 66 | solid-minor-issues | 1 | 1 | 12 | [reporte](./HelpdeskContacts.md) |
| 3 | HelpdeskAgents | 63 | half-wired | 0 | 3 | 14 | [reporte](./HelpdeskAgents.md) |
| 4 | HelpdeskDocument | 63 | needs-work | 0 | 1 | 14 | [reporte](./HelpdeskDocument.md) |
| 5 | HelpdeskTickets | 72 | solid-minor-issues | 0 | 1 | 18 | [reporte](./HelpdeskTickets.md) |
| 6 | Helpdesk (core) | 73 | solid-minor-issues | 0 | 1 | 13 | [reporte](./Helpdesk.md) |
| 7 | HelpdeskLivechat | 77 | solid-minor-issues | 0 | 1 | 8 | [reporte](./HelpdeskLivechat.md) |
| 8 | HelpdeskAnalytics | 78 | solid-minor-issues | 0 | 0 | 10 | [reporte](./HelpdeskAnalytics.md) |
| 9 | HelpdeskCampaigns | 80 | solid-minor-issues | 0 | 1 | 8 | [reporte](./HelpdeskCampaigns.md) |
| 10 | HelpdeskTranslate | 80 | solid-minor-issues | 0 | 0 | 9 | [reporte](./HelpdeskTranslate.md) |
| 11 | HelpdeskPrestashop | 81 | solid-minor-issues | 0 | 0 | 10 | [reporte](./HelpdeskPrestashop.md) |
| 12 | HelpdeskChatFlow | 82 | solid-minor-issues | 0 | 0 | 9 | [reporte](./HelpdeskChatFlow.md) |
| 13 | HelpdeskCompliance | 82 | solid-minor-issues | 0 | 0 | 8 | [reporte](./HelpdeskCompliance.md) |
| 14 | HelpdeskHelpcenter | 84 | solid-minor-issues | 0 | 0 | 6 | [reporte](./HelpdeskHelpcenter.md) |
| 15 | HelpdeskErp | 85 | solid-minor-issues | 0 | 0 | 6 | [reporte](./HelpdeskErp.md) |
| 16 | HelpdeskSla | 86 | solid-minor-issues | 0 | 0 | 11 | [reporte](./HelpdeskSla.md) |
| 17 | HelpdeskEmailLog | 88 | solid-minor-issues | 0 | 0 | 6 | [reporte](./HelpdeskEmailLog.md) |

**Totales:** 2 críticos · 10 highs · 176 hallazgos · health media ≈ 76.

---

## Atacar primero (orden recomendado de remediación)

El orden combina blast radius en producción, severidad confirmada y dependencia del resto del sistema.

1. **HelpdeskSocial — crítico de permisos (HS-01).** Toda la superficie de gestión devuelve HTTP 500 en producción por un desajuste de nombres de permisos que la suite de tests enmascara (crea permisos ad-hoc en `TestCase.php` en vez de arrancar el seeder real). Es el único crítico que rompe funcionalidad *hoy*. Alinear permisos controlador/Form Request/TestCase con los nombres en punto del seeder y arrancar el seeder real en CI (HS-02).
2. **HelpdeskContacts — crítico de integridad de datos (route-model-binding).** `ContactsMergeController` usa `Customer $winner` mientras el segmento de ruta es `{customer}`: el binding falla y la fusión puede corromper datos. Acompañado de un XSS en JS de la UI de merge. Riesgo de corrupción silenciosa de clientes.
3. **Helpdesk (core).** Es la columna vertebral de la que dependen los demás módulos: ruta `DELETE /conversation-items/{item}` a un método inexistente, **SSRF** en `LinkPreviewService` y descarga HTTP síncrona en el hilo de la petición. Arreglar la ruta, filtrar host/IP y mover la descarga a un job encolado.
4. **HelpdeskAgents (half-wired, 3 highs).** El runtime de IA nunca se conecta al flujo de conversaciones (HA-01): decidir entre cablearlo (`StartAiAgentSessionJob` en mensajes entrantes) o documentarlo dormido tras feature flag. Corregir el redirect roto de `destroy()` y los tests obsoletos `manager.helpdesk.ai.*`.
5. **HelpdeskDocument (needs-work).** Migración de autorización a medias: la mayoría de acciones mutantes siguen en rutas `api.documents.*` protegidas solo por `auth:web`. Encauzarlas por endpoints helpdesk con check de ownership, arreglar el device-upload a medio cablear y añadir tests (cero actualmente).

Después: el resto en orden de tabla. Tickets/core/Analytics comparten bugs de Carbon que conviene batchear (ver temas transversales). Los módulos 11–17 son `solid-minor-issues` sin críticos ni highs: limpieza de convención, half-wiring y tests.

---

## Temas transversales (problema → módulos afectados)

1. **Desalineación / fragilidad de permisos y autorización.** El patrón más extendido y el origen del único crítico funcional.
   - HelpdeskSocial (HS-01/HS-02, crítico — nombres en punto vs seeder, ad-hoc en tests)
   - HelpdeskChatFlow (CF-6 — role-vs-permission gating, `chatflow.*` sin asignar en seeder)
   - HelpdeskLivechat (nombres de permisos a alinear + ownership pre-chat)
   - HelpdeskAgents (tests con rutas/permisos obsoletos `manager.helpdesk.ai.*`)
   - HelpdeskErp (HE-3 — naming no lowercase)
   - HelpdeskHelpcenter (`authorize()` faltante en `apiCategories/apiSections/apiSectionArticles`)
   - HelpdeskPrestashop (endpoint `categories()` sin autorización)
   - HelpdeskDocument (rutas mutantes `api.documents.*` solo `auth:web`)

2. **Trabajo pesado síncrono en el hilo de petición / observer (debería encolarse).** Bloquea webhooks y respuestas HTTP.
   - Helpdesk core (descarga HTTP de link-preview síncrona)
   - HelpdeskChatFlow (CF-1/CF-2 — IA/HTTP de mensajes entrantes en un observer)
   - HelpdeskTickets (HT-01 — lógica de ciclo de vida duplicada `booted()` + observer)
   - HelpdeskCampaigns (HC-03 — búsqueda de variantes en ruta caliente pública de impresiones)

3. **Bugs de Carbon 3 (diffs con signo) en métricas/heurísticas.** Producen números negativos o penalizaciones que nunca disparan.
   - HelpdeskTickets (HT-02 — `diffInMinutes` con signo → métricas negativas)
   - HelpdeskAnalytics (Carbon en `HealthScoreBatchService::scoresFor`, penalización >6 meses nunca aplica)
   - HelpdeskSla (SLA-02 — heurística de % de advertencia inconsistente con la matemática de horario laboral)

4. **Features a medio cablear (half-wiring) y config/lang que no surte efecto.** Funcionalidad declarada pero inerte.
   - HelpdeskSocial (settings sin persistir, aprobaciones, IA), HelpdeskAgents (runtime + HA-04 config no llega al motor), HelpdeskDocument (device-upload, blades demo huérfanos), HelpdeskAnalytics (heatmap huérfano, config/lang/permiso export sin uso), HelpdeskCompliance (export declarado pero nunca producido), HelpdeskSla (panel settings sin implementar), HelpdeskTranslate (HT-01 set EN incompleto + HT-02 caché estático stale en workers)

5. **SSRF / exfiltración por URL externa no restringida.**
   - Helpdesk core (SSRF en `LinkPreviewService`), HelpdeskTranslate (HT-03 — provider URLs → SSRF / exfil de key DeepL). *(HelpdeskAgents implementa correctamente protección SSRF: usar como referencia.)*

6. **XSS.**
   - HelpdeskHelpcenter (XSS almacenado por ruptura de `<script>` JSON-LD), HelpdeskContacts (XSS en JS de la UI de merge)

7. **N+1 / accessors auto-cargadores.**
   - HelpdeskTickets (HT-03 `getCategoryAttribute/getStatusAttribute`), HelpdeskDocument (N+1), HelpdeskCampaigns (eager-load de variantes)

8. **Validación inline en vez de Form Request.**
   - HelpdeskCampaigns (HC-02 `bulkAction`), HelpdeskErp (HE-2 `refresh`), HelpdeskDocument

9. **Ausencia / debilidad de tests.**
   - HelpdeskDocument (cero tests), HelpdeskSla (controller/comando/policy), HelpdeskCompliance (handlers), HelpdeskHelpcenter (`EmbeddingsService`), HelpdeskAnalytics

10. **Caché no invalidada / cache-buster frágil.**
    - HelpdeskEmailLog (EL-2 — borrado masivo y purga), HelpdeskContacts y HelpdeskPrestashop (`filemtime(public_path(...))` sin guard ante asset no publicado)

---

## Quick wins globales

Cambios de alto valor y bajo esfuerzo, muchos de una sola línea:

- **XSS JSON-LD Helpcenter:** quitar `JSON_UNESCAPED_SLASHES` / añadir `JSON_HEX_TAG` en `public/helpcenter/show.blade.php`.
- **Ruta rota core:** implementar/eliminar el método del controlador detrás de `DELETE /conversation-items/{item}`.
- **Redirect roto Agents:** corregir `destroy()` a `helpdesk.ai.flows.index` y renombrar tests `manager.helpdesk.ai.*` → `helpdesk.ai.*`.
- **`authorize()` en endpoints de lectura JSON:** Helpcenter (`apiCategories/apiSections/apiSectionArticles`), Prestashop (`categories()`), Document.
- **Validación a Form Request:** Campaigns `bulkAction` (HC-02), Erp `refresh` (HE-2).
- **Integridad de datos Campaigns:** corregir el tipo de columna `customer_session_id` (HC-01).
- **Carbon signed-diff:** Analytics `scoresFor` + métricas de Tickets (HT-02) — usar `abs()` / orden correcto de operandos.
- **Conexión BD equivocada Erp (HE-1):** `CustomerTimelineService` y `WarmErpCacheCommand` usan la conexión `helpdesk` por error.
- **Invalidación de caché EmailLog (EL-2):** limpiar caches en borrado masivo y purga.
- **Guard de cache-buster:** envolver `filemtime(public_path(...))` ante asset no publicado (Contacts, Prestashop).
- **Restringir provider URLs Translate (HT-03)** a allowlist para cortar SSRF/exfil de key.

---

## Resumen de salud (módulos por estado)

| Estado | Nº | Módulos |
|--------|:--:|---------|
| solid-minor-issues | 14 | Helpdesk, HelpdeskTickets, HelpdeskChatFlow, HelpdeskLivechat, HelpdeskHelpcenter, HelpdeskPrestashop, HelpdeskCampaigns, HelpdeskErp, HelpdeskContacts, HelpdeskTranslate, HelpdeskEmailLog, HelpdeskSla, HelpdeskAnalytics, HelpdeskCompliance |
| needs-work | 2 | HelpdeskSocial, HelpdeskDocument |
| half-wired | 1 | HelpdeskAgents |

**Distribución de salud:** 1 módulo <60 · 3 en 60–69 · 2 en 70–79 · 11 ≥80. Mejor: HelpdeskEmailLog (88). Peor: HelpdeskSocial (56).
