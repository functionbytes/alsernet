# Paquete Claude Design — HelpdeskCampaigns

## Archivos a incluir

```
modules/HelpdeskCampaigns/public/css/campaigns.css          ← CSS esqueleto nuevo
.claude/audits/helpdesk-redesign/HelpdeskCampaigns.md       ← especificación completa
.claude/audits/helpdesk-redesign/SHARED-DESIGN-SYSTEM.md    ← sistema compartido
```

## Qué debe entregar Claude Design

1. **`campaigns-index.html`** — listado de campañas
   - `.hcm-page-bar` + `.hcm-filter-bar` con chips por tipo/estado
   - Toggle vista grid / lista
   - `.hcm-grid` con `.hcm-card` (thumbnail preview 160px, stats 3 columnas, badges tipo/estado)
   - `.hcm-status-live` con animación pulse
   - Dropdown acciones: editar / duplicar / pausar / ver analytics / eliminar

2. **`campaign-type-selector.html`** — modal selector al crear nueva campaña
   - `modal-dialog-centered` + `.hcm-type-grid` (3×2)
   - Tipos: Popup / Banner / Slide-in / Pantalla completa / Email / Chat
   - Cada opción: icono coloreado + nombre + descripción breve
   - Selección con borde coloreado

3. **`campaign-editor.html`** — editor split 60/40
   - `.hcm-editor-layout` grid 60fr/40fr
   - Panel izquierdo: tabs (Contenido / Targeting / Programación / A/B)
   - `.hcm-ab-bar` para variante A/B con slider de split porcentual
   - Panel derecho: preview simulado (popup / banner / slide) con toggle desktop/tablet/mobile
   - `.hcm-stepper` de aprobación: Borrador → En revisión → Aprobado → Live

4. **`approval-queue.html`** — cola de aprobación
   - `.hcm-approval-queue` con `.hcm-approval-card` (thumbnail + info + acciones)
   - ".Solicitar cambios" expande `.hcm-review-note` inline (NO modal)
   - Historial en tabla debajo

5. **`campaign-analytics.html`** — dashboard analytics por campaña
   - `.hcm-kpi-grid` (4 KPIs: impresiones / conversiones / CTR / ingresos)
   - Charts: Chart.js line (tendencia) + donut (dispositivos) + bar (por variante A/B)

6. **`campaigns.css`** — CSS refinado (prefijo `--hcm-*` / `.hcm-*`)

## Restricciones

- El editor es HTML/CSS + jQuery — NO React
- El preview del popup/banner es simulado con CSS (no iframe real)
- El drag de A/B split es un `<input type="range">` nativo Bootstrap
- NO inline styles

## Componentes críticos

```html
<!-- Campaign card -->
<div class="hcm-card">
  <div class="hcm-card__preview">
    <img src="thumb.jpg" class="hcm-card__preview-img" alt="">
    <span class="hcm-card__type-badge hcm-type hcm-type-popup">
      <i class="fas fa-window-restore me-1"></i>Popup
    </span>
    <div class="hcm-card__actions-overlay">
      <button class="btn btn-sm btn-light">Editar</button>
      <button class="btn btn-sm btn-light">Analytics</button>
    </div>
  </div>
  <div class="hcm-card__body">
    <p class="hcm-card__name">Black Friday 2026</p>
    <div class="hcm-card__stats">
      <div class="hcm-stat-mini">
        <span class="hcm-stat-mini__val">12.4K</span>
        <span class="hcm-stat-mini__label">Impresiones</span>
      </div>
      <div class="hcm-stat-mini">
        <span class="hcm-stat-mini__val">8.2%</span>
        <span class="hcm-stat-mini__label">CTR</span>
      </div>
      <div class="hcm-stat-mini">
        <span class="hcm-stat-mini__val">1,020</span>
        <span class="hcm-stat-mini__label">Convs.</span>
      </div>
    </div>
  </div>
  <div class="hcm-card__footer">
    <span class="hcm-status hcm-status-live">Live</span>
    <small class="text-muted">Hasta 30 nov</small>
  </div>
</div>

<!-- A/B bar -->
<div class="hcm-ab-bar">
  <div class="hcm-ab-variant active a">
    <span class="hcm-ab-label a">A</span>
    <div>
      <div class="fw-600 small">Variante A</div>
      <div class="hcm-ab-split-bar"><div class="hcm-ab-split-bar__fill-a" style="width:60%"></div></div>
    </div>
    <span class="ms-auto small fw-600">60%</span>
  </div>
  <div class="hcm-ab-variant b">
    <span class="hcm-ab-label b">B</span>
    <div>
      <div class="fw-600 small">Variante B</div>
      <div class="hcm-ab-split-bar"><div class="hcm-ab-split-bar__fill-a" style="width:40%"></div></div>
    </div>
    <span class="ms-auto small fw-600">40%</span>
  </div>
</div>
```
