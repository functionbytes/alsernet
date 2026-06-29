# Paquete Claude Design — HelpdeskAgents

## Archivos a incluir

```
modules/HelpdeskAgents/public/css/agents.css                ← CSS esqueleto nuevo
.claude/audits/helpdesk-redesign/HelpdeskAgents.md          ← especificación completa
.claude/audits/helpdesk-redesign/SHARED-DESIGN-SYSTEM.md    ← sistema compartido
```

## Qué debe entregar Claude Design

1. **`agents-index.html`** — página principal de equipo
   - `.hag-page-bar` con filtros y botón "+ Nuevo agente"
   - Grid de `.hag-agent-card` (avatar 64px, nombre, estado, stats 2×2, inboxes chips)
   - `.hag-status-dot` con variantes `online`, `busy`, `offline`
   - Dropdown acciones por agente (editar / suspender / ver turnos / ver vacaciones)

2. **`ai-flow-builder.html`** — constructor de flujo IA (accordion vertical)
   - Lista de nodos verticales con línea conectora entre ellos
   - `.hag-node` con borde izquierdo coloreado por tipo (trigger/condition/action/response/handoff/wait)
   - `.hag-connector` flecha CSS entre nodos
   - `.hag-add-step` botón dashed entre nodos para agregar paso
   - `.hag-branch` para nodos de condición (2 columnas: Sí / No)
   - Nodos colapsables: al colapsar muestra solo tipo + icono + resumen
   - **NO React Flow** — es HTML puro con Sortable.js para reordenar

3. **`shifts-calendar.html`** — grilla de turnos semanal
   - `.hag-shift-grid` — 7 columnas (L-D) × N filas (agentes)
   - Celdas con chips de turno (horario + color guardía/mañana/tarde/noche)
   - `.hag-coverage-bar` heatmap debajo mostrando cobertura total por hora

4. **`agent-panel.html`** — panel lateral de detalle de agente
   - `.hag-agent-panel` — offcanvas desde derecha `min(420px, 92vw)`
   - Tabs: Perfil / Inboxes / Turnos / Vacaciones / Stats

5. **`agents.css`** — CSS refinado sobre el esqueleto (prefijo `--hag-*` / `.hag-*`)

## Restricciones

- El flujo IA usa **arrays PHP** + **Sortable.js** (NO React Flow, NO canvas drag-and-drop)
- La grilla de turnos es HTML/CSS puro — sin librerías de calendario
- El panel lateral usa Bootstrap offcanvas o position fixed propio
- NO inline styles

## Componentes críticos

```html
<!-- Nodo IA tipo acción -->
<div class="hag-node hag-node--action">
  <div class="hag-node__header">
    <i class="fas fa-bolt hag-node__icon"></i>
    <span class="hag-node__type">Acción</span>
    <div class="ms-auto">
      <button class="hag-node__toggle"><i class="fas fa-chevron-up"></i></button>
      <button class="hag-node__drag"><i class="fas fa-grip-vertical"></i></button>
    </div>
  </div>
  <div class="hag-node__body">
    <div class="mb-2"><label class="form-label small">Tipo de acción</label>
      <select class="form-select form-select-sm">
        <option>Asignar a agente</option>
        <option>Enviar mensaje</option>
        <option>Crear ticket</option>
      </select>
    </div>
  </div>
</div>

<!-- Conector entre nodos -->
<div class="hag-connector"><i class="fas fa-arrow-down"></i></div>

<!-- Botón agregar paso -->
<button class="hag-add-step">
  <i class="fas fa-plus me-1"></i>Agregar paso
</button>
```
