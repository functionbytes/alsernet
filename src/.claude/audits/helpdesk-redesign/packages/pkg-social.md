# Paquete Claude Design — HelpdeskSocial

## Archivos a incluir

```
modules/HelpdeskSocial/public/css/social.css                ← CSS esqueleto nuevo
.claude/audits/helpdesk-redesign/HelpdeskSocial.md          ← especificación completa
.claude/audits/helpdesk-redesign/SHARED-DESIGN-SYSTEM.md    ← sistema compartido
```

## Qué debe entregar Claude Design

1. **`social-inbox.html`** — inbox social three-panel
   - `.helpdesk-social-page` con grid `220px 300px 1fr 280px`
   - Panel izquierdo `.hso-nav`: chips de red + navegación secciones
   - Panel lista `.hso-list`: filter chips `[Todos][Sin leer][Comentarios][DMs][WA]` + items de mensaje
   - `.hso-item` con borde izquierdo por sentimiento (verde/gris/rojo) + avatar con `.hso-net-badge.fb/ig/wa`
   - `.hso-badges-sentiment-pos/neu/neg` y `.hso-badge-intent.love/support/risk/info` en cada item
   - Panel central `.hso-thread`: header (nombre + handle + red + botones acciones + timer WA) + burbujas + composer
   - Panel derecho `.hso-right`: datos contacto, historial sentimiento, score riesgo, etiquetas

2. **`wa-timer-states.html`** — estados del timer WhatsApp (página de demo)
   - `.hso-wa-timer.ok` — > 2 horas (verde)
   - `.hso-wa-timer.warn` — 1-2 horas (naranja)
   - `.hso-wa-timer.urgent.hso-pulse` — < 1 hora (rojo pulsante)
   - `.hso-wa-timer.expired` — sesión expirada
   - Banner `.hso-wa-expired-banner` que reemplaza el composer

3. **`wa-template-modal.html`** — modal selector de plantillas WA
   - `modal-dialog-centered modal-lg`
   - Buscador + filtros Categoría/Estado
   - `.hso-template-card` con badge categoría (UTILITY/MARKETING/AUTH) + badge estado (AP/PE/RE)
   - Al seleccionar: panel de variables `{{1}}`, `{{2}}` para rellenar

4. **`competitors-dashboard.html`** — dashboard de competidores
   - Selector multi marcas + rango fechas + chips de red
   - Donut chart Chart.js (Share of Voice) con leyenda
   - Tabla comparativa de métricas con flechas de tendencia coloreadas
   - Grid horizontal scrollable de top contenido competidores

5. **`social.css`** — CSS refinado (prefijo `--hso-*` / `.hso-*`)

## Restricciones

- Three-panel con CSS Grid — NO flex horizontal
- Los badges de sentimiento/intención deben ser visibles SIN hover
- El timer WA debe ser prominente en el header del thread (no se puede pasar por alto)
- El donut Chart.js es solo el contenedor — Claude Design deja `<canvas>` con ID
- NO inline styles

## Componentes críticos

```html
<!-- Social inbox item con sentimiento negativo -->
<div class="hso-item hso-item--unread hso-item--neg">
  <div class="hso-item__avatar-wrap">
    <div class="hso-av hso-av--c3">JL</div>
    <span class="hso-net-badge wa"><i class="fab fa-whatsapp"></i></span>
  </div>
  <div class="hso-item__body">
    <div class="hso-item__header">
      <span class="hso-item__name">Juan López</span>
      <span class="hso-item__time">2m</span>
    </div>
    <div class="hso-item__type text-muted small">
      <i class="fab fa-whatsapp me-1 text-success"></i>Mensaje directo
    </div>
    <div class="hso-item__preview">Terrible servicio, llevo 3 días esperando...</div>
    <div class="hso-item__badges">
      <span class="badge hso-badge-sent-neg"><i class="fas fa-face-frown me-1"></i>Negativo</span>
      <span class="badge hso-badge-intent risk"><i class="fas fa-triangle-exclamation me-1"></i>Queja</span>
    </div>
  </div>
</div>

<!-- WA timer urgente -->
<div class="hso-wa-timer urgent hso-pulse">
  <i class="fas fa-triangle-exclamation"></i>
  <span class="hso-wa-timer__label">Sesión WA</span>
  <span class="hso-wa-timer__countdown">47m restantes</span>
</div>
```
