# Paquete Claude Design — Helpdesk (core)

## Archivos a incluir

```
modules/Helpdesk/resources/css/conversations-identity.css   ← tokens CSS globales
modules/Helpdesk/resources/css/conversations.css            ← layout + componentes
.claude/audits/helpdesk-redesign/Helpdesk.md                ← especificación completa
.claude/audits/helpdesk-redesign/SHARED-DESIGN-SYSTEM.md    ← sistema compartido
```

## Qué debe entregar Claude Design

1. **`inbox-layout.html`** — three-panel completo (nav + list + thread + right sidebar)
   - `.bv-nav` con secciones y items
   - `.bv-list` con search, filter chips, conversation items
   - `.bv-thread` con header, bubbles (in/out/note), timeline
   - `.bv-right` con contacto, timeline, etiquetas
   - `.bv-composer` con textarea, toolbar, IA suggestions

2. **`modals.html`** — 32 modales en una sola página de referencia
   - Patrón: `modal-dialog-centered` + footer botones `w-100` apilados
   - Incluir: asignar, cerrar, escalar, etiquetar, merge, snooze, CSAT, spam, bloquear

3. **`settings-layout.html`** — settings con sidebar nav + content area
   - Stepper para workflows
   - Toggle switches para reglas de automatización

4. **`conversations-refined.css`** — CSS con mejoras sobre el esqueleto existente

## Restricciones

- NO modificar el prefijo `.bv-*` — es el convención existente en producción
- NO usar `style=""` inline
- NO usar React, Alpine, Livewire
- Todos los iconos: Font Awesome 6 (`fas fa-*`, `far fa-*`, `fab fa-*`)
- Color primario: `#90bb13`
- Fuente monospace para timestamps/IDs: JetBrains Mono

## Componentes críticos del inbox

```html
<!-- Conversation item con estado no leído -->
<div class="bv-conv-item bv-conv-item--unread">
  <div class="bv-conv-item__avatar bv-av bv-av--c2">JL</div>
  <div class="bv-conv-item__body">
    <div class="bv-conv-item__header">
      <span class="bv-conv-item__name">Juan López</span>
      <span class="bv-conv-item__time">2m</span>
    </div>
    <div class="bv-conv-item__preview">Necesito ayuda con mi pedido...</div>
    <div class="bv-conv-item__meta">
      <span class="badge bv-badge-channel"><i class="fab fa-whatsapp me-1"></i>WhatsApp</span>
      <span class="badge bv-badge-status">Abierto</span>
    </div>
  </div>
</div>

<!-- Burbuja de mensaje entrante -->
<div class="bv-bubble bv-bubble--in">
  <div class="bv-bubble__text">Hola, necesito ayuda con mi pedido #12345</div>
  <div class="bv-bubble__time">14:32</div>
</div>

<!-- Nota interna -->
<div class="bv-bubble bv-bubble--note">
  <i class="fas fa-lock bv-bubble__note-icon"></i>
  <div class="bv-bubble__text">Recordar verificar el estado del envío antes de responder</div>
</div>
```
