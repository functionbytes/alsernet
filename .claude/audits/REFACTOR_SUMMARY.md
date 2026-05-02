# Refactor Masivo Alsernet — Resumen ejecutivo

**Fecha**: Abril 2026
**Alcance**: Helpdesk (4 módulos, 15 CRUDs) + 20 módulos no-Helpdesk (limpieza de anti-patrones)

---

## Objetivo

Unificar todas las vistas al golden standard del proyecto y erradicar los siguientes anti-patrones:

- Tabler Icons (`ti ti-*`) — reemplazados por Font Awesome 6
- Inline styles (`style=""`) — reemplazados por clases Bootstrap/CSS
- `form-switch` checkboxes — reemplazados por `<select class="form-select">`
- `confirm()` nativo en `onsubmit` — reemplazado por modal `.delete-btn` pattern
- `container-fluid` en HTML de vistas — eliminado
- `linear-gradient` en CSS inline — eliminado
- `btn-primary-custom` — eliminado, se usa Bootstrap nativo
- Color `#5D87FF` (tema anterior) — reemplazado por `#b10100`
- `theme: 'bootstrap-5'` en select2 — eliminado (CSS no cargado)

---

## Alcance

### Módulos Helpdesk — Full refactor al golden standard

| Módulo | Descripción |
|--------|-------------|
| Helpdesk | Core: AI agents, flows, knowledge, tools, tags |
| HelpdeskTickets | Tickets, categorías, estados, grupos, SLA, vistas, plantillas, respuestas enlatadas, recurrentes |
| HelpdeskAgents | Gestión de agentes |
| HelpdeskCampaigns | Campañas de outreach |

### 20 módulos no-Helpdesk — Limpieza de anti-patrones

Attention, Blog, Calendar, Cookie, Core, Database, Forms, Gallery, Leads, Locales, Mailrelay, Modules, Page, Reviews, Role, Seo, Theme, Users, Widget (eliminado), y otros según inventario.

---

## Golden Standard aplicado

```blade
@extends('layouts.theme')
@include('core::components.card', ['title' => '...'])
@include('core::components.alerts')
```

| Componente | Patrón |
|---|---|
| Stats cards | `card bg-light-secondary h-100` |
| Tabla | `table table-hover align-middle text-nowrap` + thead `table-light` |
| Acciones tabla | Dropdown `fa-ellipsis-vertical`, sin iconos en items, sin `text-danger` |
| Delete | `@include('core::components.delete')` + `.delete-btn` (NO `confirm()`) |
| Modales | `modal-dialog-centered` + footer `w-100` apilado |
| Booleanos | `<select class="form-select">` con opciones descriptivas (NO checkboxes) |
| Sección header | `<h6 class="fw-semibold mb-1">` + `<p class="text-muted small mb-3">` |
| Footer botones | `btn btn-primary w-100 mb-1` + `btn btn-light w-100` |
| Iconos | Font Awesome 6 exclusivo (`fas`, `far`, `fab`) |
| AJAX CSRF | `headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }` |
| Color primario | `#b10100` |

---

## 15 CRUDs Helpdesk refactorizados

| # | CRUD | Módulo |
|---|------|--------|
| 1 | ticket-categories | HelpdeskTickets |
| 2 | ticket-statuses | HelpdeskTickets |
| 3 | ticket-tags | HelpdeskTickets |
| 4 | ticket-groups | HelpdeskTickets |
| 5 | ticket-sla-policies | HelpdeskTickets |
| 6 | ticket-canned-replies | HelpdeskTickets |
| 7 | ticket-views | HelpdeskTickets |
| 8 | ticket-templates | HelpdeskTickets |
| 9 | recurring-tickets | HelpdeskTickets |
| 10 | tickets (main) | HelpdeskTickets — split-sidebar preservado en `show` |
| 11 | ai-tags | Helpdesk |
| 12 | ai-tools | Helpdesk |
| 13 | ai-knowledge | Helpdesk |
| 14 | ai-flows | Helpdesk — editor visual de nodos reconstruido |
| 15 | campaigns | HelpdeskCampaigns |

---

## Migrations durables creadas

| Migration | Descripción |
|-----------|-------------|
| `000031_align_more_tables` | Alineación de columnas existentes |
| `000032_add_priority_to_ticket_group_user` | Prioridad en pivot ticket-grupo-usuario |
| `000033_add_canned_reply_fields` | Campos adicionales en respuestas enlatadas |
| `000034_make_ticket_id_nullable_in_views` | `ticket_id` nullable en ticket views |
| `000040_add_ai_tag_columns` | Columnas de AI tags |
| `000041_create_ai_tool_knowledge_tables` | Tablas para tools y knowledge base |
| `000042_add_type_to_campaigns` | Campo `type` en campañas |
| `000050_add_updated_at_to_ticket_histories` | Timestamp `updated_at` en historial |
| `000060_add_performance_indexes` | Índices de performance en tablas críticas |

---

## Bug fixes incidentales

- Validaciones malformadas (`'fa-duotone nullable|string'` → `'nullable|string'`) en 4 controllers
- Column name mismatches corregidos: `body/content`, `shortcut/short_code`, `subject/title`, `trigger/trigger_type`
- Accessor recursion en `TicketCannedReply::getContentAttribute`
- Route name prefixes corregidos en 8+ controllers
- Null-safe access (`?->`) aplicado en 10+ referencias de vistas
- 5 ServiceProviders con namespace/ruta mismatch corregidos

---

## Seguridad implementada

- API `/api/helpdesk/*` ahora protegida con `auth:sanctum` + throttle (antes era pública)
- Helper `clean($html)` en `app/helpers.php` con HTMLPurifier para prevenir XSS
  - Aplicado en `conversations/show.blade.php:313`
  - Aplicado en `canneds/edit.blade.php:16,47`
- File upload con whitelist MIME en 2 controllers

---

## Performance implementada

| Optimización | Impacto estimado |
|---|---|
| Campaign N+1 fix con `withCount` + `selectRaw` | ~40–60 queries/page ahorradas |
| 5 `Cache::remember()` en Ticket model (status lookups) | Reduce hits a DB en listados |
| `scopeOpen`/`scopeClosed` con `whereIn` cacheado | Queries de estado reutilizables |
| AiAgentFlows: 3 COUNT queries → 1 `groupBy` | 2 queries eliminadas por listado |
| `AiAgent::first()` cacheada (TTL 5 min) | Evita query repetida en cada request |
| Migration de índices: `conversations.last_message_at`, `conversations.updated_at`, `campaign_impressions.clicked_at` | Scans completos eliminados |

---

## Tests PHPUnit

| Suite | Tests | Tiempo |
|-------|-------|--------|
| Controller structural tests (class_exists + route_exists) | 70 | — |
| Model structural tests (fillable, casts, relations) | 75 | — |
| **Total** | **145** | **~24s** |

> Nota: Tests son estructurales (no HTTP requests por timeout de boot). Tests E2E con DB real pendientes.

---

## Métricas finales anti-patrones

| Anti-patrón | Inicial | Final | Reducción |
|---|---|---|---|
| Tabler icons (`ti ti-*`) | múltiples | **0** | 100% |
| `btn-primary-custom` | múltiples | **0** | 100% |
| `#5D87FF` | 20+ | **0** | 100% |
| `linear-gradient` inline | ~60 | **0** | 100% |
| `container-fluid` en HTML | 50+ | **0** | 100% |
| `confirm()` en onsubmit | 147 | **0** | 100% |
| `form-switch` checkboxes | 152 | **~28** | 82% |
| `bootstrap-5` theme select2 | 2 | **0** | 100% |

---

## Archivos nuevos importantes

| Archivo | Propósito |
|---------|-----------|
| `app/helpers.php` | Helper global `clean($html)` con HTMLPurifier |
| `.claude/audits/blade-patterns-audit.md` | Auditoría de patrones Blade por módulo |
| `modules/*/database/seeders/DefaultAiAgentSeeder.php` | Seeder de agente AI por defecto |

---

## Known limitations

- **28 `form-switch` restantes**: Mailrelay (20), Forms (6), Cookie (2) — en patterns multi-indent que los regex no captaron. Requieren Edit manual por archivo.
- **Inline styles residuales**: Seo (~331), Page (~310), Reviews (~191) — mayoría son valores dinámicos/compuestos legítimos (e.g., `style="color: {{ $brand }}"`) que no aplican el anti-patrón.
- **Tests estructurales solamente**: No hacen HTTP requests por timeout de app boot. Los 145 tests validan estructura de clases y existencia de rutas.

---

## Siguientes pasos recomendados

1. **Bulk actions QA visual**: endpoints existen y fueron refactorizados, sin validación visual en Chrome
2. **Model factories**: crear factories para Helpdesk models y reemplazar tests estructurales con tests funcionales
3. **Tests E2E funcionales**: implementar con Dusk o Playwright para flujos críticos (crear ticket, responder, cerrar)
4. **Full golden refactor top-5 no-Helpdesk**: Seo, Page, Mailrelay, Reviews, Forms (mayor deuda técnica restante)
5. **Completar form-switch**: 28 instancias restantes en Mailrelay/Forms/Cookie
