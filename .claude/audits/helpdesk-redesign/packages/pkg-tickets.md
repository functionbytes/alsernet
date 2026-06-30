# Paquete Claude Design — HelpdeskTickets

## Archivos a incluir

```
modules/HelpdeskTickets/public/css/tickets.css              ← CSS esqueleto completo (1474 líneas)
.claude/audits/helpdesk-redesign/HelpdeskTickets.md         ← especificación completa
.claude/audits/helpdesk-redesign/SHARED-DESIGN-SYSTEM.md    ← sistema compartido
```

## Qué debe entregar Claude Design

1. **`kanban-view.html`** — vista kanban de tickets
   - Columnas: Nuevo / En progreso / Esperando cliente / Resuelto / Cerrado
   - `.htk-col` con header contador + `.htk-card` draggable (Sortable.js)
   - Cada card: título, SLA bar visual, badges prioridad/asignado/categoría
   - `.htk-card--sla-warn` y `.htk-card--sla-breach` para estados SLA

2. **`table-view.html`** — vista tabla de tickets (dxDataGrid)
   - `.htk-page-bar` + `.htk-toolbar` con filter chips y segmented control
   - `.htk-bulk` bar flotante para acciones masivas
   - `.htk-table` con columnas: ID, título, prioridad, asignado, SLA, estado, acciones
   - Dropdown de acciones con `fa-ellipsis-vertical`

3. **`detail-panel.html`** — panel de detalle slide-in (desde derecha)
   - `.htk-detail` — `position: fixed; right: 0; width: min(720px, 92vw)`
   - Header: título, badges, botones acciones
   - `.htk-msg` burbujas de conversación + `.htk-reply` composer
   - Sidebar derecho interno: metadata, contacto, SLA timeline

4. **`tickets-refined.css`** — CSS con mejoras sobre el esqueleto (NO cambiar prefijo `--htk-*` / `.htk-*`)

## Restricciones

- NO cambiar prefijos `--htk-*` y `.htk-*` — están en producción
- `partials-v2/` en las vistas está vacío — aquí irán los nuevos componentes
- NO usar React para el kanban — usar Sortable.js (jQuery plugin)
- El panel de detalle NO abre página nueva — slide-in overlay sobre la lista

## Componentes críticos

```html
<!-- Kanban card con SLA en warning -->
<div class="htk-card htk-card--sla-warn" data-id="1024">
  <div class="htk-card__header">
    <span class="htk-prio htk-prio-high"><i class="fas fa-arrow-up"></i></span>
    <span class="htk-id">#1024</span>
  </div>
  <div class="htk-card__title">Error al procesar pago con tarjeta Visa</div>
  <div class="htk-sla htk-sla--warn">
    <div class="htk-sla__bar" style="--sla-pct: 78%"></div>
    <span class="htk-sla__time"><i class="fas fa-clock me-1"></i>2h 15m</span>
  </div>
  <div class="htk-card__footer">
    <div class="htk-av htk-av--c4">MR</div>
    <span class="htk-pill htk-pill-billing">Facturación</span>
  </div>
</div>

<!-- SLA bar component -->
<div class="htk-sla htk-sla--ok">
  <div class="htk-sla__bar" style="--sla-pct: 35%"></div>
  <span class="htk-sla__time">6h 30m restantes</span>
</div>
```
